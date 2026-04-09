# Native PHP To Trait Consistency Audit

This document audits framework classes under `App/` and highlights direct native PHP calls that already have a trait-level wrapper elsewhere in the framework.

## Snapshot

- Class files scanned: `104`
- Class files with at least one replacement candidate: `0`
- Total native-call occurrences matching existing trait wrappers: `0`
- Low-friction replacement paths (`already-composed`): `0`
- Structural replacement paths (`available-via-trait`): `0`

## Reading Notes

- `already-composed` means the class already uses the trait that exposes the wrapper method, so replacement is low-friction.
- `available-via-trait` means the wrapper exists in the framework, but the class does not currently compose that trait.
- This audit only covers global native PHP calls that have an obvious existing trait wrapper. It does not try to replace every language construct or object method.

## Top Native Calls With Existing Trait Replacements


## Top Classes By Replacement Opportunity


## Per-Class Findings

