# sass-prefix-mixins
Collection of mixins to add vendor prefixes directly in sass/scss.

## install
### NPM
```
npm install sass-prefix-mixins
```
### Bower
```
bower install sass-prefix-mixins
```

## Using

Add the scss file to your your own scss file:

```scss
@import "./../bower_components/sass-autoprefixer/scss/prefixes";
```
replace your scss statement with its sass-prefix-mixins mixin, e.g. replace

```scss
.your-class {
  flex-flow: row wrap;
}
```

with

```scss
.your-class {
  @include vp-flex-flow(row wrap);
}
```

the resulting css will look like

```css
.your-class {
  -webkit-flex-flow: row wrap;
  -moz-flex-flow: row wrap;
  -ms-flex-flow: row wrap;
  flex-flow: row wrap;
}
 ```
 
## Mixins

Check the source code to see which mixins exist or help me and update this Readme
 
## See also
* [Bootstrap 4 Backward](https://github.com/JumpLinkNetwork/bootstrap-backward) - Bootstrap 4 Version using sass-autoprefixer to compile bootstrap 4 directly in your project.
