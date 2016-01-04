var gulp = require('gulp');
var livereload = require('gulp-livereload');

require('./tasks/sass');
require('./tasks/browserify');

/**
 * Defines the default (development) task for Gulp.
 */
gulp.task('default', ['sass:dev', 'watchify'], function() {
  livereload.listen();

  // Watch stylesheets
  gulp.watch([
    '**/*.scss'
  ], ['sass:dev']);

  // When compile tasks finish, trigger livereload
  gulp.watch([
    'assets/**/*.css',
    './assets/**/*.js'
  ], function(event) {
    livereload.changed(event.path);
  });
});

gulp.task('dev', ['default']);
