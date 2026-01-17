/** @type {import('@maizzle/framework').Config} */

/*
|-------------------------------------------------------------------------------
| Development config                      https://maizzle.com/docs/environments
|-------------------------------------------------------------------------------
|
| The exported object contains the default Maizzle settings for development.
| This is used when you run `maizzle build` or `maizzle serve` and it has
| the fastest build time, since most transformations are disabled.
|
*/

export default {
  build: {
    content: ['src/templates/**/*.html'],
    output: {
      path: 'public/local',
      from: ['src/templates'],
    },
    static: {
      source: ['src/images/**/*'],
      destination: 'images',
    },
  },
  baseURL: {
    url: '',
    tags: {
      img: false,
      source: false,
      a: false,
    },
  },
}
