# Social Feeds for WordPress

Social Feeds is a plugin which allows social media posts from Instagram to be integrated with any WordPress site.

## Usage

When activated, the plugin will create a "Social Posts" custom post type for managing posts. Enter your Instagram API credentials under "Feed Settings" and fill in the desired hashtags and/or users to pull from.

Use the "Select Posts" view to curate which posts will be published to the site and which will remain hidden as drafts.

## Development

PHP dependencies are managed via Composer, and should be included in the Git repository to simplify deployment.

Front-end dev dependencies (for the admin interface) are managed via NPM. The project includes a Gulp setup for compiling CSS from SCSS and bundling JavaScript using Browserify. After installing Node.js and Gulp, run `npm install` from the project directory to download the required tools. Once the dependencies are installed, you can run `gulp` to start the development task.
