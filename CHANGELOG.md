# 1.1.0

- _minor_: Added `setMinimumLogLevel` and `getMinimumLogLevel` to `SeqLogger` interface
- _minor_: Removed `$minimumLogLevel` from `SeqLogger` constructor
- Added `$minimumLogLevel` to `SeqLoggerConfigration` constructor
- `SeqHttpClient` is no longer `#[Immutable]`
- Added `getMinimumLevelAccepted()` to `SeqClient` interface
- Added ability to update minimum log level via Seq at runtime

# 1.0.1

- Fixed some edge cases for custom log levels
- Allow partial JSON encodings for rendered values in context
- Use `message` key if no context is used

# 1.0.0

- Initial release 🎉
- Support for HTTP ingestion added
- Support for PSR-3 (Logging) added
