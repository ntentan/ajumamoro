v0.5.0 - 2025-04-06
===================
Added
-----
- The logger was added to the inline broker to improve its compatibility with other brokers.
- Improved the code with typehints in various places.

Fixed
-----
- Exception handling was properly contained for cases of the broker failing at startup.


v0.4.0 - 2025-03-15
===================
Added
-----
- Proper exception handling for failed jobs to ensure daemon can recover and not die.
- Cleaner interfaces for job brokers

Fixed
-----
- Shell script can now be launched from any location.
- Unwanted warnings from RedisBroker code

v0.3.1 - 2020-03-07
===================
First release with a changelog