var gulp = require('gulp');
var browserify = require('browserify');
var watchify = require('watchify');
var shim = require('browserify-shim');
var vinylSrc = require('vinyl-source-stream');
var vinylBuffer = require('vinyl-buffer');
var path = require('path');

/** Defines the "browserify" task for Gulp. */
gulp.task('browserify', function(callback) {
  return browserifyTask(false, callback);
});

/** Defines the "watchify" task for Gulp. */
gulp.task('watchify', function() {
  return browserifyTask(true);
});

/** 
 * Runs the Browserify or Watchify bundler.
 * @param {boolean} dev - "True" to configure the task for development.
 * @param {function} cb - Async callback function.
 */
function browserifyTask(dev, cb) {
  var b = browserify({
    entries: './src/js/social-feeds-admin.js',
    output: './assets/js/social-feeds-admin.min.js',
    paths: ['./', './src/js/'],
    transform: [
      [shim, {
        global: true
      }]
    ],
    debug: true
  });

  // Add minify plugin w/ source map options
  b.plugin('minifyify', {
    map: 'social-feeds-admin.min.js.map',
    output: './assets/js/social-feeds-admin.min.js.map',
    compressPath: function(p) {
      // Start relative paths from root
      return path.join('../../', p);
    }
  });

  function bundle() {
    bundleLogger.start('social-feeds-admin.min.js.map');

    return b.bundle()
      .on('error', function (err) { console.error(err.message); })
      .on('end', function() {
        bundleLogger.end('social-feeds-admin.min.js');
      })
      .pipe(vinylSrc('social-feeds-admin.min.js'))
      .pipe(vinylBuffer())
      .pipe(gulp.dest('./assets/js/'));
  }

  if(dev) {
    b = watchify(b);
    b.on('update', bundle);
  }

  return bundle();
}

var gutil = require('gulp-util');
var prettyHrtime = require('pretty-hrtime');
var startTime;

/** Logging functions for Browserify, originally from gulp-starter. */
var bundleLogger = {
  start: function(filepath) {
    startTime = process.hrtime();
    gutil.log('Bundling', gutil.colors.green(filepath));
  },

  end: function(filepath) {
    var taskTime = process.hrtime(startTime);
    var prettyTime = prettyHrtime(taskTime);
    gutil.log('Bundled', gutil.colors.green(filepath), 'in', gutil.colors.magenta(prettyTime));
  }
};
