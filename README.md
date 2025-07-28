# DiffDetect

A REDAXO addon for monitoring changes on websites and RSS feeds. DiffDetect automatically creates snapshots of specified URLs and helps editors track modifications in various web sources over time.

## Features

- **URL Monitoring**: Track changes on HTML web pages
- **RSS Feed Monitoring**: Monitor updates in RSS feeds with specialized diff view
- **Automatic Snapshots**: Create snapshots automatically via cronjob
- **Visual Diff Display**: Compare different versions with highlighted changes
- **HTTP Authentication**: Support for password-protected pages
- **Console Command**: Manual execution via command line
- **Dashboard**: Central overview of all monitored URLs with their status
- **Flexible Intervals**: Configure individual check intervals for each URL
- **Categories**: Organize URLs with tags/categories

## Requirements

- REDAXO >= 5.15.1
- PHP >= 8.2
- YForm Addon >= 3.2
- Cronjob Addon >= 2.10.0

## Installation

1. Install the addon via REDAXO backend under "AddOns"
2. The addon will automatically install required dependencies
3. Configure cronjobs for automatic monitoring (see Configuration section)

## Usage

### Adding URLs to Monitor

1. Navigate to **AddOns > Diff Detection**
2. Click on "URL hinzufügen" (Add URL)
3. Configure the following settings:
   - **Name**: A descriptive name for the URL
   - **URL**: The full URL to monitor
   - **Type**: Choose between HTML or RSS
   - **Interval**: Check interval in minutes
   - **Categories**: Optional tags for organization
   - **HTTP Authentication**: Optional login credentials

### Viewing Changes

1. Go to the Dashboard to see all monitored URLs
2. Click on a URL to see its snapshot history
3. Select two snapshots to compare
4. The diff view will highlight all changes between versions

### Manual Snapshot Creation

You can manually trigger snapshot creation:
- Via Backend: Click "Snapshot abrufen" for a specific URL
- Via Console: `./redaxo/bin/console diff_detect:execute`

## Configuration

### Cronjob Setup

The addon uses REDAXO's cronjob system for automatic monitoring:

1. Go to **AddOns > Cronjobs**
2. Create a new cronjob
3. Select "DiffDetect: Snapshots erstellen und CleanUp durchführen"
4. Configure the execution interval (recommended: every 5-15 minutes)

### URL Configuration Options

- **Status**: Active/Inactive - temporarily disable monitoring
- **Interval**: Minimum time between checks (in minutes)
- **Type**: 
  - HTML: For regular web pages
  - RSS: For RSS/Atom feeds (uses specialized diff algorithm)
- **HTTP Authentication**: For password-protected pages

## Technical Details

### Database Structure

The addon creates two main tables:
- `rex_diff_detect_url`: Stores URL configurations
- `rex_diff_detect_index`: Stores snapshots and their content

### How It Works

1. The cronjob checks for URLs that need updating based on their interval
2. For each URL, it fetches the current content
3. If changes are detected, a new snapshot is created
4. Old snapshots are kept for comparison
5. The diff algorithm highlights changes between versions

### Diff Algorithms

- **HTML Pages**: Line-by-line comparison with whitespace normalization
- **RSS Feeds**: Item-based comparison that tracks new, changed, and removed feed items

## Console Commands

```bash
# Execute diff detection manually
./redaxo/bin/console diff_detect:execute
```

## Use Cases

- Monitor legal pages for compliance updates
- Track competitor websites for changes
- Watch news portals for specific topics
- Monitor RSS feeds for new content
- Check fact-checker websites for updates
- Track documentation pages for changes

## Support

- **GitHub**: [https://github.com/FriendsOfREDAXO/diff_detect](https://github.com/FriendsOfREDAXO/diff_detect)
- **Issues**: Report bugs and feature requests on GitHub
- **REDAXO Slack**: Get help in the REDAXO community

## License

This addon is licensed under the MIT License. See LICENSE file for details.

## Credits

Developed by Friends Of REDAXO
