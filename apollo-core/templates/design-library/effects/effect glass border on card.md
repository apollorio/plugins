Desired efect on some cards of glass border as below layer effect:



@layer effect {

&nbsp; aside {

&nbsp;   box-shadow:

&nbsp;     0px 0.5px 0.6px hsl(0 0% 0% / 0.06),

&nbsp;     0px 1.8px 2.3px -0.5px hsl(0 0% 0% / 0.07),

&nbsp;     0px 4.2px 5.3px -1.1px hsl(0 0% 0% / 0.09),

&nbsp;     0px 9.7px 12.1px -1.6px hsl(0 0% 0% / 0.1);

&nbsp;   background: linear-gradient(light-dark(hsl(0 0% 98%), hsl(0 0% 12%)) 0 100%) padding-box;

&nbsp;   padding: .5rem;

&nbsp;   border-radius: var(--border-radius);

&nbsp;   border: 4px solid light-dark(hsl(0 0% 0% / 0.1), hsl(0 0% 100% / 0.2));

&nbsp;   backdrop-filter: blur(4px) saturate(2.8) brightness(1.25) contrast(1);

&nbsp; }

&nbsp; \[data-disable='true'] aside {

&nbsp;   backdrop-filter: none;

&nbsp; }

}







###################### ENTIRE CODE FOR REFERENCE:
<html lang="en"><head>

&nbsp;   <meta charset="UTF-8">

&nbsp;   <title>frosted + saturated borders 🧑‍🍳</title>

&nbsp;   <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0, maximum-scale=1.0">

&nbsp;   <title>saturated-borders</title>

&nbsp;   <link rel="preconnect" href="https://fonts.googleapis.com">

&nbsp;   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">

&nbsp;   <link href="https://fonts.googleapis.com/css2?family=Gloria+Hallelujah\&amp;family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900\&amp;display=swap" rel="stylesheet"><style>@import url('https://unpkg.com/normalize.css') layer(normalize);



@layer normalize, base, demo, effect;



@layer effect {

&nbsp; aside {

&nbsp;   box-shadow:

&nbsp;     0px 0.5px 0.6px hsl(0 0% 0% / 0.06),

&nbsp;     0px 1.8px 2.3px -0.5px hsl(0 0% 0% / 0.07),

&nbsp;     0px 4.2px 5.3px -1.1px hsl(0 0% 0% / 0.09),

&nbsp;     0px 9.7px 12.1px -1.6px hsl(0 0% 0% / 0.1);

&nbsp;   background: linear-gradient(light-dark(hsl(0 0% 98%), hsl(0 0% 12%)) 0 100%) padding-box;

&nbsp;   padding: .5rem;

&nbsp;   border-radius: var(--border-radius);

&nbsp;   border: 4px solid light-dark(hsl(0 0% 0% / 0.1), hsl(0 0% 100% / 0.2));

&nbsp;   -webkit-backdrop-filter: blur(4px) saturate(2.8) brightness(1.25) contrast(1);

&nbsp;           backdrop-filter: blur(4px) saturate(2.8) brightness(1.25) contrast(1);

&nbsp; }

&nbsp; \[data-disable='true'] aside {

&nbsp;   -webkit-backdrop-filter: none;

&nbsp;           backdrop-filter: none;

&nbsp; }

}



@layer demo {

&nbsp; :root {

&nbsp;   --control-size: 52px;

&nbsp;   --padding: .5rem;

&nbsp;   --border-radius: 16px;

&nbsp; }



&nbsp; .arrow {

&nbsp;   font-family: 'Gloria Hallelujah', cursive;

&nbsp;   font-size: 0.875rem;

&nbsp;   position: absolute;

&nbsp;   top: calc(20vh + 160px);

&nbsp;   left: 50%;

&nbsp;   translate: -150% -50%;

&nbsp;   opacity: 0.6;

&nbsp;   white-space: nowrap;



&nbsp;   svg {

&nbsp;     position: absolute;

&nbsp;     width: 60px;

&nbsp;     bottom: 100%;

&nbsp;     left: 100%;

&nbsp;     rotate: -10deg;

&nbsp;   }

&nbsp; }



&nbsp; h1 {

&nbsp;   --font-level: 4;

&nbsp; }

&nbsp; ul {

&nbsp;   padding: 0;

&nbsp;   margin: 0;

&nbsp;   list-style: none;



&nbsp;   img {

&nbsp;     max-width: calc(100vw - 2rem);

&nbsp;     filter: saturate(1.2) contrast(1.1);

&nbsp;   }

&nbsp; }



&nbsp; main {

&nbsp;   display: grid;

&nbsp;   place-items: center;

&nbsp; }



&nbsp; aside {

&nbsp;   font-family: 'Inter', sans-serif;

&nbsp;   letter-spacing: -0.025em;

&nbsp;   margin-block: 20vh;

&nbsp;   font-size: .875rem;

&nbsp;   position: sticky;

&nbsp;   z-index: 9;

&nbsp;   top: 20vh;

&nbsp;   width: 340px;

&nbsp;   display: flex;

&nbsp;   gap: 0.5rem;



&nbsp;   .product-info {

&nbsp;     flex: 1;

&nbsp;     display: grid;

&nbsp;     align-items: center;

&nbsp;     align-content: center;

&nbsp;     gap: 0.125rem;

&nbsp;     font-size: .75rem;

&nbsp;     opacity: 0.7;



&nbsp;     \& > span:first-of-type {

&nbsp;       font-weight: 500;

&nbsp;       font-size: 1rem;

&nbsp;     }



&nbsp;     s {

&nbsp;       opacity: 0.5;

&nbsp;     }

&nbsp;   }

&nbsp;   button {

&nbsp;     height: var(--control-size);

&nbsp;     display: flex;

&nbsp;     padding-inline: 1rem;

&nbsp;     background: linear-gradient(light-dark(hsl(0 0% 100% / 0.2), hsl(0 0% 6% / 0.1)), #0000), light-dark(#000, #fff);

&nbsp;     color: light-dark(hsl(0 0% 98%), hsl(0 0% 12%));

&nbsp;     border: 0;

&nbsp;     align-items: center;

&nbsp;     font-size: .875rem;

&nbsp;     border-radius: calc(var(--border-radius) - var(--padding));



&nbsp;   }



&nbsp;   img {

&nbsp;     width: var(--control-size);

&nbsp;     aspect-ratio: 1;

&nbsp;     border-radius: calc(var(--border-radius) - var(--padding));

&nbsp;   }

&nbsp; }

}



@layer base {

&nbsp; :root {

&nbsp;   --font-size-min: 16;

&nbsp;   --font-size-max: 20;

&nbsp;   --font-ratio-min: 1.2;

&nbsp;   --font-ratio-max: 1.33;

&nbsp;   --font-width-min: 375;

&nbsp;   --font-width-max: 1500;

&nbsp; }



&nbsp; html {

&nbsp;   color-scheme: light dark;

&nbsp; }



&nbsp; \[data-theme='light'] {

&nbsp;   color-scheme: light only;

&nbsp; }



&nbsp; \[data-theme='dark'] {

&nbsp;   color-scheme: dark only;

&nbsp; }



&nbsp; :where(.fluid) {

&nbsp;   --fluid-min: calc(

&nbsp;     var(--font-size-min) \* pow(var(--font-ratio-min), var(--font-level, 0))

&nbsp;   );

&nbsp;   --fluid-max: calc(

&nbsp;     var(--font-size-max) \* pow(var(--font-ratio-max), var(--font-level, 0))

&nbsp;   );

&nbsp;   --fluid-preferred: calc(

&nbsp;     (var(--fluid-max) - var(--fluid-min)) /

&nbsp;       (var(--font-width-max) - var(--font-width-min))

&nbsp;   );

&nbsp;   --fluid-type: clamp(

&nbsp;     (var(--fluid-min) / 16) \* 1rem,

&nbsp;     ((var(--fluid-min) / 16) \* 1rem) -

&nbsp;       (((var(--fluid-preferred) \* var(--font-width-min)) / 16) \* 1rem) +

&nbsp;       (var(--fluid-preferred) \* var(--variable-unit, 100vi)),

&nbsp;     (var(--fluid-max) / 16) \* 1rem

&nbsp;   );

&nbsp;   font-size: var(--fluid-type);

&nbsp; }



&nbsp; \*,

&nbsp; \*:after,

&nbsp; \*:before {

&nbsp;   box-sizing: border-box;

&nbsp; }



&nbsp; body {

&nbsp;   background: light-dark(#fff, #000);

&nbsp;   display: grid;

&nbsp;   place-items: center;

&nbsp;   min-height: 100vh;

&nbsp;   font-family: 'SF Pro Text', 'SF Pro Icons', 'AOS Icons', 'Helvetica Neue',

&nbsp;     Helvetica, Arial, sans-serif, system-ui;

&nbsp; }



&nbsp; body::before {

&nbsp;   --size: 45px;

&nbsp;   --line: color-mix(in hsl, canvasText, transparent 80%);

&nbsp;   content: '';

&nbsp;   height: 100vh;

&nbsp;   width: 100vw;

&nbsp;   position: fixed;

&nbsp;   background: linear-gradient(

&nbsp;         90deg,

&nbsp;         var(--line) 1px,

&nbsp;         transparent 1px var(--size)

&nbsp;       )

&nbsp;       calc(var(--size) \* 0.36) 50% / var(--size) var(--size),

&nbsp;     linear-gradient(var(--line) 1px, transparent 1px var(--size)) 0%

&nbsp;       calc(var(--size) \* 0.32) / var(--size) var(--size);

&nbsp;   -webkit-mask: linear-gradient(-20deg, transparent 50%, white);

&nbsp;           mask: linear-gradient(-20deg, transparent 50%, white);

&nbsp;   top: 0;

&nbsp;   transform-style: flat;

&nbsp;   pointer-events: none;

&nbsp;   z-index: -1;

&nbsp; }



&nbsp; .bear-link {

&nbsp;   color: canvasText;

&nbsp;   position: fixed;

&nbsp;   top: 1rem;

&nbsp;   left: 1rem;

&nbsp;   width: 48px;

&nbsp;   aspect-ratio: 1;

&nbsp;   display: grid;

&nbsp;   place-items: center;

&nbsp;   opacity: 0.8;

&nbsp; }



&nbsp; :where(.x-link, .bear-link):is(:hover, :focus-visible) {

&nbsp;   opacity: 1;

&nbsp; }



&nbsp; .bear-link svg {

&nbsp;   width: 75%;

&nbsp; }



&nbsp; /\* Utilities \*/

&nbsp; .sr-only {

&nbsp;   position: absolute;

&nbsp;   width: 1px;

&nbsp;   height: 1px;

&nbsp;   padding: 0;

&nbsp;   margin: -1px;

&nbsp;   overflow: hidden;

&nbsp;   clip: rect(0, 0, 0, 0);

&nbsp;   white-space: nowrap;

&nbsp;   border-width: 0;

&nbsp; }

}



div.tp-dfwv {

&nbsp; width: 256px;

&nbsp; position: fixed;

}</style>



&nbsp; </head>

&nbsp;   

&nbsp; <body>

&nbsp; <main>

&nbsp;     <aside>

&nbsp;       <img src="https://assets.codepen.io/605876/the-cap-boardwalk.png" alt="">

&nbsp;       <div class="product-info">

&nbsp;         <span>Cap 001</span>

&nbsp;         <div class="product-details">

&nbsp;           <span>Drag me • <s>$30</s> $23</span>

&nbsp;         </div>

&nbsp;       </div>

&nbsp;       <button aria-label="Add to cart">

&nbsp;         <span>

&nbsp;           <span class="add-to-cart"></span>Add to cart</span>

&nbsp;         

&nbsp;       </button>

&nbsp;     </aside>

&nbsp;    

&nbsp;       

&nbsp;     



&nbsp;    

&nbsp;   </main>

&nbsp;   <script type="module">

import gsap from 'https://cdn.skypack.dev/gsap@3.13.0';

import Draggable from 'https://cdn.skypack.dev/gsap@3.13.0/Draggable';

import { Pane } from 'https://cdn.skypack.dev/tweakpane@4.0.4';

gsap.registerPlugin(Draggable);



const config = {

&nbsp; theme: 'system',

&nbsp; blur: 8,

&nbsp; saturate: 2.8,

&nbsp; brightness: 1.25,

&nbsp; contrast: 1,

&nbsp; disable: false,

&nbsp; border: 2 };





const ctrl = new Pane({

&nbsp; title: 'config',

&nbsp; expanded: true });





const update = () => {

&nbsp; document.documentElement.dataset.theme = config.theme;

&nbsp; document.documentElement.dataset.disable = config.disable;

&nbsp; document.documentElement.style.setProperty('--blur', config.blur);

&nbsp; document.documentElement.style.setProperty('--saturate', config.saturate);

&nbsp; document.documentElement.style.setProperty('--brightness', config.brightness);

&nbsp; document.documentElement.style.setProperty('--contrast', config.contrast);

&nbsp; document.documentElement.style.setProperty('--border', config.border);

};



const sync = event => {

&nbsp; if (

&nbsp; !document.startViewTransition ||

&nbsp; event.target.controller.view.labelElement.innerText !== 'theme')



&nbsp; return update();

&nbsp; document.startViewTransition(() => update());

};



ctrl.addBinding(config, 'blur', {

&nbsp; label: 'blur',

&nbsp; min: 0,

&nbsp; max: 20,

&nbsp; step: 1 });





ctrl.addBinding(config, 'saturate', {

&nbsp; label: 'saturate',

&nbsp; min: 0,

&nbsp; max: 10,

&nbsp; step: 0.1 });



ctrl.addBinding(config, 'brightness', {

&nbsp; label: 'brightness',

&nbsp; min: 0,

&nbsp; max: 2,

&nbsp; step: 0.01 });



ctrl.addBinding(config, 'contrast', {

&nbsp; label: 'contrast',

&nbsp; min: 0,

&nbsp; max: 3,

&nbsp; step: 0.1 });



ctrl.addBinding(config, 'border', {

&nbsp; label: 'border',

&nbsp; min: 0,

&nbsp; max: 10,

&nbsp; step: 1 });



ctrl.addBinding(config, 'disable');



ctrl.addBinding(config, 'theme', {

&nbsp; label: 'theme',

&nbsp; options: {

&nbsp;   system: 'system',

&nbsp;   light: 'light',

&nbsp;   dark: 'dark' } });







ctrl.on('change', sync);

update();



// make tweakpane panel draggable

const tweakClass = 'div.tp-dfwv';

const d = Draggable.create(tweakClass, {

&nbsp; type: 'x,y',

&nbsp; allowEventDefault: true,

&nbsp; trigger: tweakClass + ' button.tp-rotv\_b' });



document.querySelector(tweakClass).addEventListener('dblclick', () => {

&nbsp; gsap.to(tweakClass, {

&nbsp;   x: `+=${d\[0].x \* -1}`,

&nbsp;   y: `+=${d\[0].y \* -1}`,

&nbsp;   onComplete: () => {

&nbsp;     gsap.set(tweakClass, { clearProps: 'all' });

&nbsp;   } });



});



const aside = document.querySelector('aside');

const a = Draggable.create(aside, {

&nbsp; type: 'x,y',

&nbsp; allowEventDefault: true });



aside.addEventListener('dblclick', () => {

&nbsp; gsap.to(aside, {

&nbsp;   x: `+=${a\[0].x \* -1}`,

&nbsp;   y: `+=${a\[0].y \* -1}`,

&nbsp;   onComplete: () => {

&nbsp;     gsap.set(aside, { clearProps: 'all' });

&nbsp;   } });



});

</script>



&nbsp; 

&nbsp; 



</body></html>

