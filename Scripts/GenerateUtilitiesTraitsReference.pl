#!/usr/bin/env perl

use strict;
use warnings;

use File::Find;
use File::Spec;

sub slurp_file {
	my ($path) = @_;

	open my $fh, '<', $path or die "Unable to read $path: $!";
	local $/;
	return <$fh>;
}

sub trim {
	my ($value) = @_;
	$value //= '';
	$value =~ s/^\s+//;
	$value =~ s/\s+$//;
	return $value;
}

sub normalize_space {
	my ($value) = @_;
	$value //= '';
	$value =~ s/\R/ /g;
	$value =~ s/\s+/ /g;
	$value =~ s/\s+,/,/g;
	$value =~ s/\(\s+/\(/g;
	$value =~ s/\s+\)/)/g;
	$value =~ s/\{\s+/\{/g;
	$value =~ s/\s+\{/\{/g;
	$value =~ s/\s+;/;/g;
	$value =~ s/\s+\|/\|/g;
	$value =~ s/\|\s+/\|/g;
	$value =~ s/:\s+\?/: ?/g;
	return trim($value);
}

sub strip_line_comments {
	my ($value) = @_;
	$value //= '';
	$value =~ s{//[^\n\r]*}{}g;
	$value =~ s{/\*.*?\*/}{}gs;
	return $value;
}

sub first_doc_summary_before_line {
	my ($lines_ref, $line_index, $name) = @_;

	my $cursor = $line_index - 2;
	while ($cursor >= 0 && $lines_ref->[$cursor] =~ /^\s*$/) {
		$cursor--;
	}

	return undef if $cursor < 0 || $lines_ref->[$cursor] !~ m{\*/};

	my @doc_lines;
	while ($cursor >= 0) {
		unshift @doc_lines, $lines_ref->[$cursor];
		last if $lines_ref->[$cursor] =~ m{/\*\*};
		$cursor--;
	}

	return undef if !@doc_lines || $doc_lines[0] !~ m{/\*\*};

	my @clean;
	for my $line (@doc_lines) {
		$line =~ s/^\s*\/\*\*?\s?//;
		$line =~ s/\*\/\s*$//;
		$line =~ s/^\s*\*\s?//;
		$line = trim($line);
		next if $line eq '';
		next if $line =~ /^(?:Trait|Class)\s+[A-Za-z_][A-Za-z0-9_]*$/;
		next if $line =~ /^\@/;
		push @clean, $line;
	}

	return $clean[0];
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

	my @imports;
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
					push @imports, $import->{fqcn};
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
				push @imports, $import->{fqcn};
				$aliases{$import->{alias}} = $import->{fqcn};
			}
			$buffer = '';
		}
	}

	my %seen;
	@imports = grep { !$seen{$_}++ } @imports;

	return (\@imports, \%aliases);
}

sub count_char {
	my ($line, $char) = @_;
	my $count = () = $line =~ /\Q$char\E/g;
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

sub parse_trait_declarations {
	my ($path) = @_;
	my $content = slurp_file($path);
	my @lines = map { "$_\n" } split /\n/, $content, -1;

	my ($namespace) = $content =~ /^\s*namespace\s+([^;]+);/m;
	$namespace = trim($namespace // '');

	my ($trait_name, $trait_line_index);
	for my $index (0 .. $#lines) {
		if ($lines[$index] =~ /^\s*trait\s+([A-Za-z_][A-Za-z0-9_]*)\b/) {
			$trait_name = $1;
			$trait_line_index = $index;
			last;
		}
	}

	die "Unable to parse trait name from $path" if !defined $trait_name;

	my ($imports_ref) = parse_header_imports(\@lines, $trait_line_index);
	my @imports = @$imports_ref;

	my $summary = first_doc_summary_before_line(\@lines, $trait_line_index + 1, $trait_name);

	my @properties;
	my @methods;
	my @composed_traits;

	my $in_trait = 0;
	my $depth = 0;
	my $capturing = 0;
	my $capture_type = '';
	my $capture_start = 0;
	my $capture = '';
	my $capture_paren_depth = 0;

	for my $index ($trait_line_index .. $#lines) {
		my $line = $lines[$index];

		if (!$in_trait) {
			if ($line =~ /\{/) {
				$in_trait = 1;
				$depth = count_char($line, '{') - count_char($line, '}');
			}
			next;
		}

		if (!$capturing && $depth == 1) {
			if ($line =~ /^\s*use\b/) {
				$capturing = 1;
				$capture_type = 'use';
				$capture_start = $index + 1;
				$capture = $line;
			} elsif ($line =~ /^\s*(?:public|protected|private)\b/ && $line =~ /\$/ && $line !~ /\bfunction\b/) {
				$capturing = 1;
				$capture_type = 'property';
				$capture_start = $index + 1;
				$capture = $line;
			} elsif ($line =~ /^\s*(?:final\s+)?(?:public|protected|private)\b/ && $line =~ /\bfunction\b/) {
				$capturing = 1;
				$capture_type = 'method';
				$capture_start = $index + 1;
				$capture = $line;
				$capture_paren_depth = count_char($line, '(') - count_char($line, ')');
			}
		} elsif ($capturing) {
			$capture .= $line;
			if ($capture_type eq 'method') {
				$capture_paren_depth += count_char($line, '(') - count_char($line, ')');
			}
		}

		if ($capturing) {
			my $done = 0;

			if ($capture_type eq 'use') {
				my $capture_depth = count_char($capture, '{') - count_char($capture, '}');
				$done = 1 if $capture =~ /;\s*$/ || ($capture =~ /\{/ && $capture_depth == 0);
			} elsif ($capture_type eq 'property') {
				$done = 1 if $capture =~ /;\s*$/;
			} elsif ($capture_type eq 'method') {
				$done = 1 if $capture_paren_depth <= 0 && $capture =~ /(?:\{|\;)\s*$/s;
			}

			if ($done) {
				my $normalized = normalize_space($capture);
				$normalized =~ s/\s*\{\s*$//;
				$normalized =~ s/\s*;\s*$//;

				if ($capture_type eq 'use') {
					push @composed_traits, parse_trait_use_names($normalized);
				} elsif ($capture_type eq 'property') {
					push @properties, {
						line      => $capture_start,
						signature => $normalized,
					};
				} elsif ($capture_type eq 'method') {
					push @methods, {
						line      => $capture_start,
						signature => $normalized,
					};
				}

				$capturing = 0;
				$capture_type = '';
				$capture_start = 0;
				$capture = '';
				$capture_paren_depth = 0;
			}
		}

		$depth += count_char($line, '{') - count_char($line, '}');
		last if $depth == 0;
	}

	my %composed_seen;
	@composed_traits = grep { !$composed_seen{$_}++ } @composed_traits;

	my $kind = @methods || @properties ? 'method provider' : @composed_traits ? 'wrapper trait' : 'trait';

	return {
		name           => $trait_name,
		namespace      => $namespace,
		path           => $path,
		summary        => $summary,
		imports        => \@imports,
		composed       => \@composed_traits,
		properties     => \@properties,
		methods        => \@methods,
		kind           => $kind,
		category       => category_for_path($path),
		fqcn           => $namespace . '\\' . $trait_name,
		has_constructor => scalar(grep { $_->{signature} =~ /\bfunction\s+__construct\s*\(/ } @methods) ? 1 : 0,
	};
}

sub category_for_path {
	my ($path) = @_;
	my $relative = $path;
	$relative =~ s{^(?:\./)?App/Utilities/Traits/?}{};
	$relative =~ s{^.*?/App/Utilities/Traits/?}{};

	return 'Core Traits' if $relative !~ m{/};

	my ($category) = split m{/}, $relative, 2;
	return ucfirst($category);
}

sub parse_php_consumers {
	my ($root_path, $trait_map_ref) = @_;
	my %consumers;

	my @php_files;
	find(
		sub {
			return if $File::Find::name !~ /\.php$/;
			return if $File::Find::name =~ m{(?:^|/)\.?/?App/Utilities/Traits/};
			push @php_files, $File::Find::name if $File::Find::name =~ m{(?:^|/)\.?/?App/};
		},
		File::Spec->catdir($root_path, 'App')
	);

	for my $path (sort @php_files) {
		my $content = slurp_file($path);
		my @lines = map { "$_\n" } split /\n/, $content, -1;

		my ($namespace) = $content =~ /^\s*namespace\s+([^;]+);/m;
		$namespace = trim($namespace // '');

		my $class_line_index;
		for my $index (0 .. $#lines) {
			if ($lines[$index] =~ /^\s*(?:abstract\s+|final\s+)?(?:class|trait|interface)\s+[A-Za-z_][A-Za-z0-9_]*\b/) {
				$class_line_index = $index;
				last;
			}
		}

		next if !defined $class_line_index;

		my ($imports_ref, $aliases_ref) = parse_header_imports(\@lines, $class_line_index);
		my %aliases = %{$aliases_ref};

		my $in_body = 0;
		my $depth = 0;
		my $capturing = 0;
		my $capture = '';

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
				$capture = $line;
			} elsif ($capturing) {
				$capture .= $line;
			}

			if ($capturing) {
				my $capture_depth = count_char($capture, '{') - count_char($capture, '}');
				if ($capture =~ /;\s*$/ || ($capture =~ /\{/ && $capture_depth == 0)) {
					my $normalized = normalize_space($capture);
					my @names = parse_trait_use_names($normalized);

					for my $name (@names) {
						my $resolved = resolve_name($name, \%aliases, $namespace);
						next if !exists $trait_map_ref->{$resolved};
						push @{ $consumers{$resolved} }, $path;
					}

					$capturing = 0;
					$capture = '';
				}
			}

			$depth += count_char($line, '{') - count_char($line, '}');
			last if $depth == 0;
		}
	}

	for my $trait (keys %consumers) {
		my %seen;
		@{ $consumers{$trait} } = grep { !$seen{$_}++ } @{ $consumers{$trait} };
	}

	return \%consumers;
}

sub resolve_name {
	my ($name, $aliases_ref, $namespace) = @_;

	$name = trim($name);
	$name =~ s/^\\//;

	return $aliases_ref->{$name} if exists $aliases_ref->{$name};
	return $name if $name =~ /\\/;

	return $namespace ne '' ? $namespace . '\\' . $name : $name;
}

sub vis_from_signature {
	my ($signature) = @_;
	return $1 if $signature =~ /^(public|protected|private)\b/;
	return 'unknown';
}

my $repo_root = shift // '.';
my $traits_dir = File::Spec->catdir($repo_root, 'App', 'Utilities', 'Traits');

my @trait_files;
find(
	sub {
		return if $File::Find::name !~ /\.php$/;
		push @trait_files, $File::Find::name;
	},
	$traits_dir
);
@trait_files = sort @trait_files;

my @traits = map { parse_trait_declarations($_) } @trait_files;
my %trait_map = map { $_->{fqcn} => $_ } @traits;
my $consumers_ref = parse_php_consumers($repo_root, \%trait_map);

for my $trait (@traits) {
	$trait->{consumers} = $consumers_ref->{ $trait->{fqcn} } // [];
}

my $trait_count = scalar @traits;
my $method_count = 0;
my $property_count = 0;
my $wrapper_count = 0;
my $constructor_count = 0;
my $unused_count = 0;

for my $trait (@traits) {
	$method_count += scalar @{ $trait->{methods} };
	$property_count += scalar @{ $trait->{properties} };
	$wrapper_count++ if $trait->{kind} eq 'wrapper trait';
	$constructor_count++ if $trait->{has_constructor};
	$unused_count++ if !@{ $trait->{consumers} };
}

my @categories = (
	'Core Traits',
	'Criteria',
	'Filters',
	'Iterator',
	'Patterns',
	'Query',
	'Reflection',
	'Rules',
	'Sort',
);

print "# Utilities Traits Reference\n\n";
print "This document is generated directly from the current PHP source under `App/Utilities/Traits`.\n";
print "It is intended to complement `Docs/UtilitiesTraitsOverview.md` with full per-trait coverage.\n\n";

print "## Snapshot\n\n";
print "- Trait files: `$trait_count`\n";
print "- Total properties declared in traits: `$property_count`\n";
print "- Total methods declared in traits: `$method_count`\n";
print "- Wrapper traits: `$wrapper_count`\n";
print "- Traits with `__construct()`: `$constructor_count`\n";
print "- Traits with no current `App/` consumer: `$unused_count`\n\n";

print "## Coverage Notes\n\n";
print "- `Current consumers` only includes concrete usage inside `App/` outside the traits directory.\n";
print "- `Imports` lists file-level imports declared before the trait.\n";
print "- `Composed traits` lists traits mixed into the trait body itself.\n";
print "- `Properties` and `Methods` include line numbers to make navigation easier.\n\n";

print "## Category Index\n\n";
for my $category (@categories) {
	my @in_category = grep { $_->{category} eq $category } @traits;
	next if !@in_category;
	print "- `$category`: ";
	print join(', ', map { '`' . $_->{name} . '`' } @in_category);
	print "\n";
}
print "\n";

for my $category (@categories) {
	my @in_category = grep { $_->{category} eq $category } @traits;
	next if !@in_category;

	print "## $category\n\n";

	for my $trait (@in_category) {
		print "### `$trait->{name}`\n\n";
		print "- Path: `$trait->{path}`\n";
		print "- FQCN: `$trait->{fqcn}`\n";
		print "- Type: `$trait->{kind}`\n";
		print "- Summary: `$trait->{summary}`\n" if defined $trait->{summary};

		if (@{ $trait->{imports} }) {
			print "- Imports: ";
			print join(', ', map { '`' . $_ . '`' } @{ $trait->{imports} });
			print "\n";
		}

		if (@{ $trait->{composed} }) {
			print "- Composed traits: ";
			print join(', ', map { '`' . $_ . '`' } @{ $trait->{composed} });
			print "\n";
		}

		if (@{ $trait->{consumers} }) {
			print "- Current consumers: ";
			print join(', ', map { '`' . $_ . '`' } @{ $trait->{consumers} });
			print "\n";
		} else {
			print "- Current consumers: none found outside the traits layer\n";
		}

		if (@{ $trait->{properties} }) {
			print "- Properties:\n";
			for my $property (@{ $trait->{properties} }) {
				print "  - `$property->{signature}` at `$trait->{path}:$property->{line}`\n";
			}
		}

		if (@{ $trait->{methods} }) {
			my @public = grep { vis_from_signature($_->{signature}) eq 'public' } @{ $trait->{methods} };
			my @protected = grep { vis_from_signature($_->{signature}) eq 'protected' } @{ $trait->{methods} };
			my @private = grep { vis_from_signature($_->{signature}) eq 'private' } @{ $trait->{methods} };

			print "- Methods: `" . scalar(@{ $trait->{methods} }) . "` total";
			print " (`" . scalar(@public) . "` public, `" . scalar(@protected) . "` protected, `" . scalar(@private) . "` private)\n";
			for my $method (@{ $trait->{methods} }) {
				print "  - `$method->{signature}` at `$trait->{path}:$method->{line}`\n";
			}
		} else {
			print "- Methods: none declared directly\n";
		}

		print "\n";
	}
}
