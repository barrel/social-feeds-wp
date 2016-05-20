# Social Feeds for WordPress

Social Feeds is a plugin which allows social media posts from Twitter, Instagram, and or a LinkedIn company page to be integrated with any WordPress site.

## Usage

Once activated, the plugin will create a "Social Posts" custom post type for managing posts.

You can configure each network's feed independently by providing your own API credentials and adjusting what content should be pulled, and how often.

Use the "Select Posts" view to curate which posts will be published to the site and which will remain hidden as drafts. You also have the option of automatically publishing new posts when setting up a scheduled sync.

## Post Type

The plugin does not currently provide any helpers for displaying the social feeds on the front-end. Instead, posts are saved to a custom post type. How these posts are displayed is left to the theme author. The following data is saved with each post:

__Post Title__: [Original post content]

__Post Content__: [Original post content (with linked URLs)]

__Social Type__ (taxonomy): [Network]

__Custom Fields__:
- `social_post_created`: [Original post timestamp]
- `social_post_details`: [Original post JSON]
- `social_post_permalink`: [Origial post URL]
- `social_post_username`: [Origial post author]
- `social_post_image`: [Origial post image (if any)]
- `social_post_video`: [Origial post video (if any)]

## Settings

Plugin settings are managed on the respective network's settings page, located under the "Social Posts" post type in the dashboard menu. You can also configure options by defining constants in your theme's functions.php or wp-config.php files.

## Development

PHP dependencies are managed via Composer, and are included in the Git repository to simplify deployment.

Front-end dev dependencies (for the admin interface) are managed via NPM. The project includes a Gulp setup for compiling CSS from SCSS and bundling JavaScript using Browserify. After installing Node.js and Gulp, run `npm install` from the project directory to download the required tools. Once the dependencies are installed, you can run `gulp` to start the development task.
