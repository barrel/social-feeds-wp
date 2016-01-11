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

  // When compile tasks finish, trigger livereload
  gulp.watch([
    'assets/**/*.css',
    './assets/**/*.js'
  ]);
});

gulp.task('dev', ['default']);
