(()=>{"use strict";var e,t={1209:()=>{const e=window.React,t=JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"plover-kit/counter","title":"Plover: Counter","category":"plover-blocks","description":"Add an animated numbered counter to your page","keywords":["counter","number"],"textdomain":"plover-kit","version":"1.0.0","attributes":{"align":{"type":"string"},"justification":{"type":"string"}},"supports":{"anchor":true,"html":false,"align":["wide","full"],"color":{"text":true,"background":true,"gradients":true},"spacing":{"margin":true,"padding":true,"blockGap":true},"typography":{"fontSize":true,"lineHeight":true,"__experimentalFontFamily":true,"__experimentalFontWeight":true,"__experimentalFontStyle":true,"__experimentalTextTransform":true,"__experimentalTextDecoration":true,"__experimentalLetterSpacing":true,"__experimentalDefaultControls":{"fontSize":true}},"__experimentalBorder":{"radius":true,"color":true,"width":true,"style":true,"__experimentalDefaultControls":{"radius":true,"color":true,"width":true,"style":true}}},"editorScript":"file:./index.js","render":"file:./render.php","style":"file:./style-index.min.css"}'),r=window.wp.blockEditor,n=window.wp.components,o=window.wp.i18n,l=window.wp.hooks,i=window.plover.utils,a=window.plover.components;function p(t){return(0,e.createElement)(n.SVG,{xmlns:"http://www.w3.org/2000/svg",version:"1.1",viewBox:"0 0 24 24",...t},(0,e.createElement)("g",null,(0,e.createElement)(n.Path,{d:"M20 18h4v2h-4v4h-2v-4h-4v-2h4v-4h2zM11 6v4.586l-2.707 2.707 1.414 1.414L13 11.414V6zm-9 6a10 10 0 1 1 19.949 1h2c.028-.331.051-.662.051-1a12 12 0 1 0-12 12c.338 0 .669-.023 1-.051v-2A9.992 9.992 0 0 1 2 12z",fill:"currentColor"})))}const s=window.wp.blocks,{name:c}=t;!function(e){if(!e)return;const{metadata:t,settings:r,name:n}=e;(0,s.registerBlockType)({name:n,...t},r)}({name:c,metadata:t,settings:{icon:(0,e.createElement)(p,null),example:{},edit:function(t){let s=(0,l.applyFilters)("plover.counter.BlockEdit",null,t);if(!s){const t=(0,r.useBlockProps)();s=(0,e.createElement)("div",{...t},(0,e.createElement)(n.Placeholder,{icon:(0,e.createElement)(p,{width:20}),label:(0,e.createElement)("span",{style:{padding:"0 6px"}},(0,o.__)("Plover: Counter","plover-kit")),className:"plover-counter-block-placeholder"},(0,e.createElement)("p",null,(0,e.createElement)("span",{dangerouslySetInnerHTML:{__html:(0,o.sprintf)(/* translators: %s is the premium text and link. */ /* translators: %s is the premium text and link. */
(0,o.__)("Upgrade to %s to access this block.","plover-kit"),'<a href="'+(0,i.upsell_url)()+'" target="_blank">'+(0,o.__)("Premium","plover-kit")+"</a>")}}),(0,e.createElement)("a",{href:"https://wpplover.com/docs/plover-kit/modules/counter-block/",target:"_blank",style:{padding:"0 4px"}},(0,o.__)("Learn More ↗","plover-kit")))))}return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(r.InspectorControls,null,(0,e.createElement)(a.DocLink,{borderTop:!0,link:"https://wpplover.com/docs/plover-kit/modules/counter-block/",text:(0,o.__)("Read Documentation ↗","plover-kit")})),s)},variations:[{name:"default",isDefault:!0,attributes:{style:{typography:{fontSize:"88px"}}}}]}})}},r={};function n(e){var o=r[e];if(void 0!==o)return o.exports;var l=r[e]={exports:{}};return t[e](l,l.exports,n),l.exports}n.m=t,e=[],n.O=(t,r,o,l)=>{if(!r){var i=1/0;for(c=0;c<e.length;c++){for(var[r,o,l]=e[c],a=!0,p=0;p<r.length;p++)(!1&l||i>=l)&&Object.keys(n.O).every((e=>n.O[e](r[p])))?r.splice(p--,1):(a=!1,l<i&&(i=l));if(a){e.splice(c--,1);var s=o();void 0!==s&&(t=s)}}return t}l=l||0;for(var c=e.length;c>0&&e[c-1][2]>l;c--)e[c]=e[c-1];e[c]=[r,o,l]},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={632:0,888:0};n.O.j=t=>0===e[t];var t=(t,r)=>{var o,l,[i,a,p]=r,s=0;if(i.some((t=>0!==e[t]))){for(o in a)n.o(a,o)&&(n.m[o]=a[o]);if(p)var c=p(n)}for(t&&t(r);s<i.length;s++)l=i[s],n.o(e,l)&&e[l]&&e[l][0](),e[l]=0;return n.O(c)},r=globalThis.webpackChunkplover_kit=globalThis.webpackChunkplover_kit||[];r.forEach(t.bind(null,0)),r.push=t.bind(null,r.push.bind(r))})();var o=n.O(void 0,[888],(()=>n(1209)));o=n.O(o)})();