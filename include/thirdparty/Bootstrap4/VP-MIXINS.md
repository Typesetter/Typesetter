# How to use vendor prefix mixins (vp-mixins) #

Instead of plain CSS expressions you should use these vp-mixins which will add the vendor prefixes required by legacy web browsers

### Flexbox ###

| MODERN CSS					| VP-MIXIN								| POSSIBLE VALUES |
| :---------						| :---------						| :--- |
| `display: flex`				| `@include vp-flexbox`					| _none_ |
| `display: inline-flex`		| `@include vp-inline-flex`				| _none_ |
| `flex-direction:` _value_		| `@include vp-flex-dir(`_value_`)`		| `row`(default), `row-reverse`,<br/>`column`, `column-reverse` |
| `flex-wrap:` _value_			| `@include vp-flex-wrap(`_value_`)`	| `nowrap`(default),<br/>`wrap`, `wrap-reverse` |
| `order:` _value_				| `@include vp-order(`_value_`)`		| _integer_ e.g. `0`(default),<br/>`1`, `2`, ... |
| `flex-grow:` _value_			| `@include vp-flex-grow(`_value_`)`	| _integer_ e.g. `0`(default),<br/>`1`, `2`, ... |
| `flex-shrink:` _value_		| `@include vp-flex-shrink(`_value_`)`	| _integer_ e.g. `0`, `1`(default),`2`, ... |
| `flex-basis:` _value_			| `@include vp-flex-basis(`_value_`)`	| `auto`(default),<br/>_any valid non-negative css width_ |
| `justify-content:` _value_	| `@include vp-flex-just(`_value_`)`	| `flex-start`(default),<br/>`flex-end`, `center`,<br/>`space-between`, `space-around` |
| `align-items:` _value_		| `@include vp-align-items(`_value_`)`	| `flex-start`, `flex-end`,<br/>`center`, `baseline`,<br/>`stretch`(default) |
| `align-self:` _value_			| `@include vp-align-self(`_value_`)`	| `auto`(default),<br/>`flex-start`, `flex-end`,<br/>`center`, `baseline`, `stretch` |
| `align-content:` _value_		| `@include vp-align-self(`_value_`)`	| `flex-start`, `flex-end`,<br/>`center`, `space-between`,<br/>`space-around`,<br/>`stretch` (default) |


### Flexbox Shorthands ###

| MODERN CSS							| VP-MIXIN												| POSSIBLE VALUES |
| :---------							| :---------											| :--- |
| `flex:` _value1_ _value2_ _value3_	| `@include vp-flex(` _value1_ _value2_ _value3_ `)`	| e.g. `1 1 0`, see the [Specs](http://w3.org/tr/css3-flexbox/#flex-property) |
| `flex-flow:` _value_					| `@include vp-flex-flow(`_value_`)`					| e.g. `row nowrap`, see the [Specs](http://w3.org/tr/css3-flexbox/#flex-flow-property) |


### Backgrounds ###

| MODERN CSS										| VP-MIXIN										| POSSIBLE VALUES |
| :---------										| :---------									| :--- |
| `background-size:` _value(s)_						| `@include vp-background-size(`_value(s)_`)`	| `contain`, `cover`,<br/>_single size_, _width_ _height_ |
| `background-clip:` _value(s)_						| `@include vp-background-clip(`_value(s)_`)`	| `border-box`(default),<br/>`padding-box`, `content-box` |
| `background: linear-gradient(`_values_`)`			| `@include vp-linear-gradient(`_values_`)`		| _direction_ _color-stops_ |
| `background-image: linear-gradient(`_values_`)`	| `@include vp-linear-gradient-img(`_values_`)`	| _direction_ _color-stops_ |


### Form Placeholder ###

| MODERN CSS	| VP-MIXIN	 | POSSIBLE VALUES |
| :--- | :--- | :--- |
| _selector_`::placeholder {`<br/>&nbsp;&nbsp;`color:` _value1_ <br/>&nbsp;&nbsp;`opacity: `_value2_ <br/>`}` | _selector_ `{`<br/>&nbsp;&nbsp;`@include vp-placeholder(`_value1_`,` _value2_`)`<br/>`}`| _css color_<br/>_float(0-1)_ |


### General ###

| MODERN CSS						| VP-MIXIN										| POSSIBLE VALUES |
| :---------						| :---------									| :--- |
| `display:` _value_				| `@include vp-display(`_value_`)`				| `flex`, `inline-flex` | _the mixin can also_<br/>_be used for all other display values_<br/>_but it is not required_ |
| `position: sticky`				| `@include vp-position(sticky)`				| `sticky` _other position values_<br/>_do not require the mixin_ |
| `overflow:` _value_				| `@include vp-overflow(`_value_`)`				| `visible`, `hidden`,<br/>`scroll`, `auto`, `inherit` |
| `overflow-x:` _value_				| `@include vp-overflow-x(`_value_`)`			| `visible`, `hidden`,<br/>`scroll`, `auto`, `inherit` |
| `overflow-y:` _value_				| `@include vp-overflow-y(`_value_`)`			| `visible`, `hidden`,<br/>`scroll`, `auto`, `inherit` |
| `box-sizing:` _value_				| `@include vp-box-sizing(`_value_`)`			| `content-box`(default), `border-box`,<br/>`inherit`, `initial`, `unset` |
| `box-shadow: _values_`			| `@include vp-box-shadow(`_values_`)`			| e.g. `0 2px 3px 5px #666`, see the [Specs](https://www.w3.org/TR/css-backgrounds-3/#the-box-shadow) |
| `touch-action:` _value_			| `@include vp-touch-action(`_value_`)`			| `auto`, `none`,<br/>[ [ `pan-x`, `pan-left`, `pan-right` ],<br/>[ `pan-y`, `pan-up`, `pan-down` ],<br/>`pinch-zoom` ],`manipulation` |
| `user-select:` _value_			| `@include vp-user-select(`_value_`)`			| `text`, `all`, `none`,<br/>`inherit` see [MDN](https://developer.mozilla.org/de/docs/Web/CSS/touch-action)
| `column-count:` _value_			| `@include vp-column-count(`_value_`)`			| _integer_ |
| `column-gap:` _value_				| `@include vp-column-gap(`_value_`)`			| _any css length value_ |
| `appearance:` _value_				| `@include vp-appearance(`_value_`)`			| `none`, `button`,<br/>_others_ see [MDN](https://developer.mozilla.org/docs/Web/CSS/appearance) |
| `transform:` _values_				| `@include vp-transform(`_values_`)`			| e.g. `translateX(100px) scale(1.5)`<br/>see the [Specs](https://www.w3.org/TR/css-transforms-1) |
| `backface-visibility:` _value_	| `@include vp-backface-visibility(`_value_`)`	| `visible`, `hidden` |
| `perspective:` _value_			| `@include vp-perspective(`_value_`)`			| _any css length value_ |


### Transitions ###

| MODERN CSS							| VP-MIXIN												| POSSIBLE VALUES |
| :---------							| :---------											| :--- |
| `transition:` _values_				| `@include vp-transition(`_values_`)`					| e.g. `all 0.5s` see the [Specs](https://www.w3.org/TR/css-transitions-1/#transition-shorthand-property)
| `transition-timing-function:` _value_	| `@include vp-transition-timing-function(`_value_`)`	| `ease`(default), `linear`,<br/>`ease`, `ease-in`,<br/>`ease-out`, `ease-in-out`,<br/>`cubic-bezier()`<br/>see the [Specs](https://www.w3.org/TR/css-transitions-1/#transition-timing-function-property) |
| `transition-duration:` _value_		| `@include vp-transition-duration(`_value_`)`			| e.g. `0.5s`, `0s`(default),<br/>_any valid css time_<br/>see the [Specs](https://www.w3.org/TR/css-transitions-1/#transition-delay-property) |
| `transition-property:` _value_		| `@include vp-transition-property(`_value_`)`			| e.g. `all`(default)<br/>see the [Specs](https://www.w3.org/TR/css-transitions-1/#transition-property-property) |


### Animation ###

| MODERN CSS				| VP-MIXIN									| POSSIBLE VALUES |
| :---------				| :---------								| :--- |
| `animation:` _values_		| `@include vp-animation(`_values_`)`		| see the [Specs](https://www.w3.org/TR/css-animations-1/#animations) |
| `@keyframes` _expression_	| `@include vp-keyframes(`_expression_`)`	| see the [Specs](https://www.w3.org/TR/css-animations-1/#keyframes) |
