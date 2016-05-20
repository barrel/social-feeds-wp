var gulp = require('gulp');

require('./tasks/sass');
require('./tasks/browserify');

/**
 * Defines the default (development) task for Gulp.
 */
gulp.task('default', ['sass:dev', 'watchify'], function() {
  // Watch stylesheets
  gulp.watch([
    '**/*.scss'
  ], ['sass:dev']);
});

gulp.task('dev', ['default']);
