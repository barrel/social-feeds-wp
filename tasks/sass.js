var gulp = require('gulp');
var sass = require('gulp-sass');
var extname = require('gulp-extname');
var sourcemaps = require('gulp-sourcemaps');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');
var glob = require('glob');
var path = require('path');

/** Defines the "sass" task for Gulp. */
gulp.task('sass', function() {
  return sassTask(false);
});

/** Defines the "sass:dev" task for Gulp. */
gulp.task('sass:dev', function() {
  return sassTask(true);
});

function sassTask(dev) {
  var sassOpts = {
    outputStyle: dev ? 'nested' : 'compressed',
    importer: moduleStylesImporter
  };
  
  return gulp.src('./src/scss/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass(sassOpts).on('error', sass.logError))
    .pipe(postcss([autoprefixer()]))
    .pipe(extname('.min.css'))
    .pipe(sourcemaps.write('../../assets/css/', {
      sourceRoot: '../../src/scss/'
    }))
    .pipe(gulp.dest('./assets/css'));
}

/** 
* Custom importer for node-sass.
* Makes module styles available to import as "modules/[name]".
*/
function moduleStylesImporter(url, prev) {
  if(url.indexOf('modules/') === 0) {
    var name = path.basename(url);
    var files = glob.sync('./modules/'+name+'/'+name+'.scss');

    if(files.length > 0) {
      return {
        file: files[0]
      };
    }
  }

  return prev;
}
