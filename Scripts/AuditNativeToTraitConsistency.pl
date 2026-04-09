#!/usr/bin/env perl

use strict;
use warnings;

use File::Find;
use File::Spec;

sub trim {
	my ($value) = @_;
	$value //= '';
	$value =~ s/^\s+//;
	$value =~ s/\s+$//;
	return $value;
}

sub slurp_file {
	my ($path) = @_;
	open my $fh, '<', $path or die "Unable to read $path: $!";
	local $/;
	return <$fh>;
}

sub strip_line_comments {
	my ($value) = @_;
	$value //= '';
	$value =~ s{//[^\n\r]*}{}g;
	$value =~ s{/\*.*?\*/}{}gs;
	return $value;
}

sub expand_use_statement {
	my ($statement) = @_;

	$statement = strip_line_comments($statement);
	$statement = trim($statement);
	$statement =~ s/^use\s+//;
	$statement =~ s/;$//;
	$statement = trim($statement);

	return () if $statement eq '';
	return () if $statement =~ /^(?:function|const)\b/;

	my @imports;

	if ($statement =~ /^(.*?\\)\{(.*)\}$/s) {
		my $prefix = trim($1);
		my $body = $2;

		for my $part (split /,/, $body) {
			$part = strip_line_comments($part);
			$part = trim($part);
			next if $part eq '';

			my ($name, $alias) = $part =~ /^(.*?)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i
				? (trim($1), $2)
				: ($part, undef);

			my $fqcn = $prefix . $name;
			$fqcn =~ s/^\\//;
			$alias //= ($fqcn =~ /([^\\]+)$/)[0];

			push @imports, {
				fqcn  => $fqcn,
				alias => $alias,
			};
		}
	} else {
		my ($name, $alias) = $statement =~ /^(.*?)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i
			? (trim($1), $2)
			: ($statement, undef);

		$name =~ s/^\\//;
		$alias //= ($name =~ /([^\\]+)$/)[0];

		push @imports, {
			fqcn  => $name,
			alias => $alias,
		};
	}

	return @imports;
}

sub parse_header_imports {
	my ($lines_ref, $end_index) = @_;

	my %aliases;
	my $buffer = '';
	my $capturing = 0;

	for my $i (0 .. $end_index - 1) {
		my $line = $lines_ref->[$i];

		if (!$capturing && $line =~ /^\s*use\b/) {
			$capturing = 1;
			$buffer = $line;

			if (strip_line_comments($line) =~ /;\s*$/) {
				$capturing = 0;
				for my $import (expand_use_statement($buffer)) {
					$aliases{$import->{alias}} = $import->{fqcn};
				}
				$buffer = '';
			}

			next;
		}

		next if !$capturing;

		$buffer .= $line;
		if (strip_line_comments($line) =~ /;\s*$/) {
			$capturing = 0;
			for my $import (expand_use_statement($buffer)) {
				$aliases{$import->{alias}} = $import->{fqcn};
			}
			$buffer = '';
		}
	}

	return \%aliases;
}

sub count_char {
	my ($value, $char) = @_;
	my $count = () = $value =~ /\Q$char\E/g;
	return $count;
}

sub parse_trait_use_names {
	my ($statement) = @_;
	my $prefix = trim($statement);
	$prefix =~ s/\{.*$//s;
	$prefix =~ s/^use\s+//;
	$prefix =~ s/;\s*$//;
	$prefix = trim($prefix);

	my @names;
	for my $part (split /,/, $prefix) {
		$part = strip_line_comments($part);
		$part = trim($part);
		next if $part eq '';
		$part =~ s/\s+as\s+.+$//i;
		push @names, $part;
	}

	return @names;
}

sub resolve_name {
	my ($name, $aliases_ref, $namespace) = @_;

	$name = trim($name);
	$name =~ s/^\\//;

	return $aliases_ref->{$name} if exists $aliases_ref->{$name};
	return $name if $name =~ /\\/;

	return $namespace ne '' ? $namespace . '\\' . $name : $name;
}

sub strip_comments_from_line {
	my ($line, $in_block_ref) = @_;
	my $output = '';
	my $cursor = $line;

	while ($cursor ne '') {
		if ($$in_block_ref) {
			if ($cursor =~ s/^(.*?)\*\///s) {
				$$in_block_ref = 0;
				next;
			}

			return '';
		}

		if ($cursor =~ s/^([^\/]*)\/\*/$1/s) {
			$output .= $1;
			$$in_block_ref = 1;
			next;
		}

		if ($cursor =~ s/^([^\/]*)\/\/.*$/$1/s) {
			$output .= $1;
			last;
		}

		$output .= $cursor;
		last;
	}

	return $output;
}

sub parse_class_file {
	my ($path) = @_;
	my $content = slurp_file($path);
	my @lines = map { "$_\n" } split /\n/, $content, -1;

	my ($namespace) = $content =~ /^\s*namespace\s+([^;]+);/m;
	$namespace = trim($namespace // '');

	my ($class_name, $class_line_index);
	for my $index (0 .. $#lines) {
		if ($lines[$index] =~ /^\s*(?:abstract\s+|final\s+|readonly\s+)*class\s+([A-Za-z_][A-Za-z0-9_]*)\b/) {
			$class_name = $1;
			$class_line_index = $index;
			last;
		}
	}

	return undef if !defined $class_name;

	my $aliases_ref = parse_header_imports(\@lines, $class_line_index);
	my %aliases = %{$aliases_ref};

	my @used_traits;
	my $in_body = 0;
	my $depth = 0;
	my $capturing = 0;
	my $buffer = '';

	for my $index ($class_line_index .. $#lines) {
		my $line = $lines[$index];

		if (!$in_body) {
			if ($line =~ /\{/) {
				$in_body = 1;
				$depth = count_char($line, '{') - count_char($line, '}');
			}
			next;
		}

		if (!$capturing && $depth == 1 && $line =~ /^\s*use\b/) {
			$capturing = 1;
			$buffer = $line;
		} elsif ($capturing) {
			$buffer .= $line;
		}

		if ($capturing) {
			my $capture_depth = count_char($buffer, '{') - count_char($buffer, '}');
			if ($buffer =~ /;\s*$/ || ($buffer =~ /\{/ && $capture_depth == 0)) {
				my $normalized = $buffer;
				$normalized =~ s/\R/ /g;
				$normalized =~ s/\s+/ /g;
				for my $name (parse_trait_use_names($normalized)) {
					push @used_traits, resolve_name($name, \%aliases, $namespace);
				}
				$capturing = 0;
				$buffer = '';
			}
		}

		$depth += count_char($line, '{') - count_char($line, '}');
		last if $depth == 0;
	}

	my %seen;
	@used_traits = grep { !$seen{$_}++ } @used_traits;

	return {
		path       => $path,
		namespace  => $namespace,
		class_name => $class_name,
		traits     => \@used_traits,
		lines      => \@lines,
	};
}

my %native_map = (
	'array_filter' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'filter', note => 'Direct array filtering already has a framework wrapper.' },
	],
	'array_key_exists' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'keyExists', note => 'Framework array access already exposes a dedicated key check.' },
	],
	'array_key_first' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'keyFirst', note => 'Use the shared array helper for first-key access.' },
	],
	'array_key_last' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'keyLast', note => 'Use the shared array helper for last-key access.' },
	],
	'array_keys' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'getKeys', note => 'A framework-level key extraction helper already exists.' },
	],
	'array_map' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'map', note => 'Use the existing array mapping helper for consistency.' },
	],
	'array_merge' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'merge', note => 'Prefer the shared array merge helper.' },
	],
	'array_pop' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'pop', note => 'Prefer the shared array pop helper.' },
	],
	'array_reduce' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'reduce', note => 'Prefer the shared array reduction helper.' },
	],
	'array_replace' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'replaceElements', note => 'Prefer the framework array replacement helper.' },
	],
	'array_replace_recursive' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'replaceRecursive', note => 'A framework recursive replace helper already exists.' },
	],
	'array_search' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'search', note => 'Prefer the shared array search helper.' },
	],
	'array_sum' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'sum', note => 'Use the shared array summation helper.' },
	],
	'array_unique' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'unique', note => 'Use the framework unique helper for arrays.' },
	],
	'array_values' => [
		{ trait => 'App\\Utilities\\Traits\\ArrayTrait', method => 'getValues', note => 'Prefer the shared array values helper.' },
	],
	'base64_decode' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'base64DecodeString', note => 'Use the encoding helper to keep binary/text conversions centralized.' },
	],
	'base64_encode' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'base64EncodeString', note => 'Use the encoding helper to keep binary/text conversions centralized.' },
	],
	'class_exists' => [
		{ trait => 'App\\Utilities\\Traits\\ExistenceCheckerTrait', method => 'classExists', note => 'A framework existence check wrapper already exists.' },
	],
	'filter_var' => [
		{ trait => 'App\\Utilities\\Traits\\Filters\\FiltrationTrait', method => 'var', note => 'Filter calls can align with the filtration trait surface.' },
	],
	'function_exists' => [
		{ trait => 'App\\Utilities\\Traits\\ExistenceCheckerTrait', method => 'functionExists', note => 'A framework existence check wrapper already exists.' },
	],
	'html_entity_decode' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'decodeHtmlEntitiesString', note => 'Prefer the encoding helper for entity decoding.' },
	],
	'htmlentities' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'encodeHtmlEntitiesString', note => 'Prefer the encoding helper for entity encoding.' },
	],
	'htmlspecialchars' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'encodeSpecialCharsString', note => 'Prefer the shared encoding helper for special-char escaping.' },
		{ trait => 'App\\Utilities\\Traits\\ManipulationTrait', method => 'escapeHtml', note => 'This helper also exposes HTML escaping.' },
	],
	'in_array' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isInArray', note => 'Prefer the shared inclusion check helper.' },
	],
	'interface_exists' => [
		{ trait => 'App\\Utilities\\Traits\\ExistenceCheckerTrait', method => 'interfaceExists', note => 'A framework existence check wrapper already exists.' },
	],
	'is_array' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isArray', note => 'Prefer the shared type helper for array checks.' },
	],
	'is_bool' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isBool', note => 'Prefer the shared type helper for boolean checks.' },
	],
	'is_callable' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isCallable', note => 'Prefer the shared type helper for callable checks.' },
	],
	'is_float' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isFloat', note => 'Prefer the shared type helper for float checks.' },
	],
	'is_int' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isInt', note => 'Prefer the shared type helper for integer checks.' },
	],
	'is_numeric' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isNumeric', note => 'Prefer the shared type helper for numeric checks.' },
		{ trait => 'App\\Utilities\\Traits\\CheckerTrait', method => 'isDigitString', note => 'Digit-only string validation also exists in the checker trait.' },
	],
	'is_object' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isObject', note => 'Prefer the shared type helper for object checks.' },
	],
	'is_scalar' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isScalar', note => 'Prefer the shared type helper for scalar checks.' },
	],
	'is_string' => [
		{ trait => 'App\\Utilities\\Traits\\TypeCheckerTrait', method => 'isString', note => 'Prefer the shared type helper for string checks.' },
	],
	'json_decode' => [
		{ trait => 'App\\Utilities\\Traits\\ConversionTrait', method => 'fromJson', note => 'Prefer the shared JSON decoding helper.' },
	],
	'json_encode' => [
		{ trait => 'App\\Utilities\\Traits\\ConversionTrait', method => 'toJson', note => 'Prefer the shared JSON encoding helper.' },
	],
	'method_exists' => [
		{ trait => 'App\\Utilities\\Traits\\ExistenceCheckerTrait', method => 'methodExists', note => 'A framework existence check wrapper already exists.' },
	],
	'preg_match' => [
		{ trait => 'App\\Utilities\\Traits\\Patterns\\PatternTrait', method => 'match', note => 'Use the shared regex helper when regex behavior should align across the framework.' },
	],
	'preg_match_all' => [
		{ trait => 'App\\Utilities\\Traits\\Patterns\\PatternTrait', method => 'matchAll', note => 'Use the shared regex helper when regex behavior should align across the framework.' },
	],
	'preg_quote' => [
		{ trait => 'App\\Utilities\\Traits\\Patterns\\PatternTrait', method => 'quote', note => 'Use the shared regex helper for quoting patterns.' },
	],
	'preg_replace' => [
		{ trait => 'App\\Utilities\\Traits\\Patterns\\PatternTrait', method => 'replaceByPattern', note => 'Use the shared regex helper when regex behavior should align across the framework.' },
	],
	'preg_replace_callback' => [
		{ trait => 'App\\Utilities\\Traits\\Patterns\\PatternTrait', method => 'replaceCallback', note => 'Use the shared regex helper when regex behavior should align across the framework.' },
	],
	'preg_split' => [
		{ trait => 'App\\Utilities\\Traits\\Patterns\\PatternTrait', method => 'splitByPattern', note => 'Use the shared regex split helper when regex behavior should align across the framework.' },
	],
	'property_exists' => [
		{ trait => 'App\\Utilities\\Traits\\ExistenceCheckerTrait', method => 'propertyExists', note => 'A framework existence check wrapper already exists.' },
	],
	'rawurldecode' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'decodeStringFromRawUrl', note => 'Prefer the shared encoding helper for raw URL decoding.' },
	],
	'rawurlencode' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'encodeStringForRawUrl', note => 'Prefer the shared encoding helper for raw URL encoding.' },
	],
	'rtrim' => [
		{ trait => 'App\\Utilities\\Traits\\ManipulationTrait', method => 'trimRight', note => 'Prefer the shared string-trimming helper.' },
	],
	'setlocale' => [
		{ trait => 'App\\Utilities\\Traits\\LocaleUtilityTrait', method => 'applyLocale', note => 'Locale changes can be centralized through the locale utility trait.' },
	],
	'str_contains' => [
		{ trait => 'App\\Utilities\\Traits\\CheckerTrait', method => 'contains', note => 'Prefer the shared string check helper.' },
	],
	'str_ends_with' => [
		{ trait => 'App\\Utilities\\Traits\\CheckerTrait', method => 'endsWith', note => 'Prefer the shared string check helper.' },
	],
	'str_replace' => [
		{ trait => 'App\\Utilities\\Traits\\ManipulationTrait', method => 'replaceText', note => 'Prefer the shared string replacement helper where string semantics are intended.' },
	],
	'str_starts_with' => [
		{ trait => 'App\\Utilities\\Traits\\CheckerTrait', method => 'startsWith', note => 'Prefer the shared string check helper.' },
	],
	'strtolower' => [
		{ trait => 'App\\Utilities\\Traits\\ManipulationTrait', method => 'toLower', note => 'Prefer the shared string case helper.' },
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'convertStringToLower', note => 'Use the encoding-aware lowercase helper when multibyte support matters.' },
	],
	'strtoupper' => [
		{ trait => 'App\\Utilities\\Traits\\ManipulationTrait', method => 'toUpper', note => 'Prefer the shared string case helper.' },
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'convertStringToUpper', note => 'Use the encoding-aware uppercase helper when multibyte support matters.' },
	],
	'substr' => [
		{ trait => 'App\\Utilities\\Traits\\ManipulationTrait', method => 'substring', note => 'Prefer the shared string slicing helper.' },
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'getSubstringOfString', note => 'Use the encoding-aware substring helper when multibyte support matters.' },
	],
	'trait_exists' => [
		{ trait => 'App\\Utilities\\Traits\\ExistenceCheckerTrait', method => 'traitExists', note => 'A framework existence check wrapper already exists.' },
	],
	'trim' => [
		{ trait => 'App\\Utilities\\Traits\\ManipulationTrait', method => 'trimString', note => 'Prefer the shared string-trimming helper.' },
	],
	'urldecode' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'decodeStringFromUrl', note => 'Prefer the shared encoding helper for URL decoding.' },
	],
	'urlencode' => [
		{ trait => 'App\\Utilities\\Traits\\EncodingTrait', method => 'encodeStringForUrl', note => 'Prefer the shared encoding helper for URL encoding.' },
	],
);

my @class_files;
find(
	sub {
		return if $File::Find::name !~ /\.php$/;
		return if $File::Find::name =~ m{(?:^|/)\.?/?App/Utilities/Traits/};
		push @class_files, $File::Find::name if $File::Find::name =~ m{(?:^|/)\.?/?App/};
	},
	File::Spec->catdir('.', 'App')
);

@class_files = sort @class_files;

my @classes;
for my $path (@class_files) {
	my $parsed = parse_class_file($path);
	push @classes, $parsed if defined $parsed;
}

my @occurrences;
my %files_with_matches;
my %by_function;
my %by_trait;
my %by_file;
my %status_counts = (
	'already-composed' => 0,
	'available-via-trait' => 0,
);

for my $class (@classes) {
	my %used_traits = map { $_ => 1 } @{ $class->{traits} };
	my $in_block = 0;

	for my $index (0 .. $#{ $class->{lines} }) {
		my $line = $class->{lines}[$index];
		my $code = strip_comments_from_line($line, \$in_block);
		next if trim($code) eq '';

		for my $native (sort keys %native_map) {
			next if !@{ $native_map{$native} };
			next if $code !~ /(?<!->)(?<!::)\b\Q$native\E\s*\(/;

			my @replacements;
			for my $candidate (@{ $native_map{$native} }) {
				my $status = $used_traits{ $candidate->{trait} } ? 'already-composed' : 'available-via-trait';
				push @replacements, {
					%{$candidate},
					status => $status,
				};
				$by_trait{ $candidate->{trait} }++;
				$status_counts{$status}++;
			}

			push @occurrences, {
				file         => $class->{path},
				class_name   => $class->{class_name},
				line         => $index + 1,
				native       => $native,
				code         => trim($code),
				replacements => \@replacements,
			};

			$files_with_matches{ $class->{path} } = 1;
			$by_function{$native}++;
			$by_file{ $class->{path} }{occurrences}++;
			for my $replacement (@replacements) {
				$by_file{ $class->{path} }{ $replacement->{status} }++;
			}
		}
	}
}

my %occurrences_by_file;
for my $occurrence (@occurrences) {
	push @{ $occurrences_by_file{ $occurrence->{file} } }, $occurrence;
}

my $class_count = scalar @classes;
my $matched_file_count = scalar keys %files_with_matches;
my $occurrence_count = scalar @occurrences;

print "# Native PHP To Trait Consistency Audit\n\n";
print "This document audits framework classes under `App/` and highlights direct native PHP calls that already have a trait-level wrapper elsewhere in the framework.\n\n";

print "## Snapshot\n\n";
print "- Class files scanned: `$class_count`\n";
print "- Class files with at least one replacement candidate: `$matched_file_count`\n";
print "- Total native-call occurrences matching existing trait wrappers: `$occurrence_count`\n";
print "- Low-friction replacement paths (`already-composed`): `$status_counts{'already-composed'}`\n";
print "- Structural replacement paths (`available-via-trait`): `$status_counts{'available-via-trait'}`\n\n";

print "## Reading Notes\n\n";
print "- `already-composed` means the class already uses the trait that exposes the wrapper method, so replacement is low-friction.\n";
print "- `available-via-trait` means the wrapper exists in the framework, but the class does not currently compose that trait.\n";
print "- This audit only covers global native PHP calls that have an obvious existing trait wrapper. It does not try to replace every language construct or object method.\n\n";

print "## Top Native Calls With Existing Trait Replacements\n\n";
for my $native (sort { $by_function{$b} <=> $by_function{$a} || $a cmp $b } keys %by_function) {
	print "- `$native`: `$by_function{$native}` occurrence(s)\n";
}
print "\n";

print "## Top Classes By Replacement Opportunity\n\n";
for my $file (
	sort {
		($by_file{$b}{occurrences} // 0) <=> ($by_file{$a}{occurrences} // 0)
			|| ($by_file{$b}{'already-composed'} // 0) <=> ($by_file{$a}{'already-composed'} // 0)
			|| $a cmp $b
	} keys %by_file
) {
	print "- `$file`: `"
		. ($by_file{$file}{occurrences} // 0)
		. "` native-call occurrence(s), `"
		. ($by_file{$file}{'already-composed'} // 0)
		. "` low-friction replacement path(s), `"
		. ($by_file{$file}{'available-via-trait'} // 0)
		. "` structural replacement path(s)\n";
}
print "\n";

print "## Per-Class Findings\n\n";
for my $file (sort keys %occurrences_by_file) {
	my @items = @{ $occurrences_by_file{$file} };
	my %class_traits = map { $_ => 1 } @{ (grep { $_->{path} eq $file } @classes)[0]->{traits} };

	print "### `$file`\n\n";

	if (%class_traits) {
		print "- Current traits: ";
		print join(', ', map { '`' . $_ . '`' } sort keys %class_traits);
		print "\n";
	} else {
		print "- Current traits: none\n";
	}

	for my $item (@items) {
		print "- Line `$item->{line}` uses native `$item->{native}` in ``$item->{code}``\n";
		for my $replacement (@{ $item->{replacements} }) {
			print "  - `$replacement->{status}` -> `$replacement->{trait}::$replacement->{method}()`";
			print " (`$replacement->{note}`)\n";
		}
	}

	print "\n";
}
