#!/usr/bin/env python3
"""
Parse Apache access logs to extract message view timestamps.
Generates SQL UPDATE statements to backfill viewed_at.
"""

import re
import sys
from datetime import datetime
from collections import OrderedDict

MONTHS = {
    'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
    'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
    'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
}

# Regex patterns
LOG_PATTERN = re.compile(r'\[(\d+)/(\w+)/(\d+):(\d+:\d+:\d+)')
URL_PATTERN = re.compile(r'GET /s/([a-zA-Z0-9]+)')

def parse_timestamp(match):
    """Convert log timestamp to SQL datetime format."""
    day, month, year, time = match.groups()
    month_num = MONTHS.get(month, '01')
    return f"{year}-{month_num}-{day.zfill(2)} {time}"

def main():
    if len(sys.argv) > 1:
        log_file = open(sys.argv[1], 'r')
    else:
        log_file = sys.stdin

    first_views = OrderedDict()

    for line in log_file:
        # Skip non-200 responses
        if ' 200 ' not in line:
            continue

        url_match = URL_PATTERN.search(line)
        ts_match = LOG_PATTERN.search(line)

        if url_match and ts_match:
            message_url = url_match.group(1)
            timestamp = parse_timestamp(ts_match)

            # Only keep first view
            if message_url not in first_views:
                first_views[message_url] = timestamp

    if log_file != sys.stdin:
        log_file.close()

    # Output SQL
    print("-- Backfill viewed_at from Apache access logs")
    print("-- Generated:", datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    print(f"-- Found {len(first_views)} unique message views")
    print()
    print("BEGIN TRANSACTION;")
    print()

    for url, ts in first_views.items():
        print(f"UPDATE messages SET viewed_at = '{ts}' WHERE message_url = '{url}' AND viewed_at IS NULL;")

    print()
    print("COMMIT;")

if __name__ == '__main__':
    main()
