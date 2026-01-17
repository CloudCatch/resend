/** @type {import('@maizzle/framework').Config} */

/*
|-------------------------------------------------------------------------------
| Production config                       https://maizzle.com/docs/environments
|-------------------------------------------------------------------------------
|
| This is where you define settings that optimize your emails for production.
| These will be merged on top of the base config.js, so you only need to
| specify the options that are changing.
|
*/

export default {
  build: {
    output: {
      path: 'public',
    },
  },
  css: {
     inline: true,
     purge: {},
     shorthand: true,
  },
  prettify: true,
}
