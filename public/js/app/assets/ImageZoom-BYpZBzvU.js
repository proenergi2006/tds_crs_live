import{P as T}from"./index-9tI8BQBI.js";import{d as O,o as W,f as H,g as r,h as c,w as f,u as i,F as N,C as E,i as C,s as M,t as z}from"./main-CeS48KJa.js";import"./Button.vue_vue_type_script_setup_true_lang-qNMd5tCb.js";+function(){function p(h){var m=h.getBoundingClientRect(),y=window.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop||0,n=window.pageXOffset||document.documentElement.scrollLeft||document.body.scrollLeft||0;return{top:m.top+y,left:m.left+n}}var B=80,x=function(){function h(){var o=document.createElement("img");o.onload=function(){g=Number(o.height),b=Number(o.width),m()},o.src=e.currentSrc||e.src}function m(){a=document.createElement("div"),a.className="zoom-img-wrap",a.style.position="absolute",a.style.top=p(e).top+"px",a.style.left=p(e).left+"px",l=e.cloneNode(),l.style.visibility="hidden",e.style.width=e.offsetWidth+"px",e.parentNode.replaceChild(l,e),document.body.appendChild(a),a.appendChild(e),e.classList.add("zoom-img"),e.setAttribute("data-action","zoom-out"),s=document.createElement("div"),s.className="zoom-overlay",document.body.appendChild(s),y(),n()}function y(){e.offsetWidth;var o=b,t=g,u=o/e.width,v=window.innerHeight-B,k=window.innerWidth-B,P=o/t,S=k/v;w=o<k&&t<v?u:P<S?v/t*u:k/o*u}function n(){e.offsetWidth;var o=p(e),t=window.pageYOffset,u=t+window.innerHeight/2,v=window.innerWidth/2,k=o.top+e.height/2,P=o.left+e.width/2,S=Math.round(u-k),j=Math.round(v-P),L="scale("+w+")",A="translate("+j+"px, "+S+"px) translateZ(0)";e.style.webkitTransform=L,e.style.msTransform=L,e.style.transform=L,a.style.webkitTransform=A,a.style.msTransform=A,a.style.transform=A,document.body.classList.add("zoom-overlay-open")}function _(){if(document.body.classList.remove("zoom-overlay-open"),document.body.classList.add("zoom-overlay-transitioning"),e.style.webkitTransform="",e.style.msTransform="",e.style.transform="",a.style.webkitTransform="",a.style.msTransform="",a.style.transform="",!1 in document.body.style)return d();a.addEventListener("transitionend",d),a.addEventListener("webkitTransitionEnd",d)}function d(){e.removeEventListener("transitionend",d),e.removeEventListener("webkitTransitionEnd",d),a&&a.parentNode&&(e.classList.remove("zoom-img"),e.style.width="",e.setAttribute("data-action","zoom"),l.parentNode.replaceChild(e,l),a.parentNode.removeChild(a),s.parentNode.removeChild(s),document.body.classList.remove("zoom-overlay-transitioning"))}var g=null,b=null,s=null,w=null,e=null,a=null,l=null;return function(o){return e=o,{zoomImage:h,close:_,dispose:d}}}();(function(){function h(){document.body.addEventListener("click",function(t){t.target.getAttribute("data-action")==="zoom"&&t.target.tagName==="IMG"&&m(t)})}function m(t){if(t.stopPropagation(),!document.body.classList.contains("zoom-overlay-open")){if(t.metaKey||t.ctrlKey)return y();n({forceDispose:!0}),a=x(t.target),a.zoomImage(),_()}}function y(){window.open(event.target.getAttribute("data-original")||event.target.currentSrc||event.target.src,"_blank")}function n(t){t=t||{forceDispose:!1},a&&(a[t.forceDispose?"dispose":"close"](),d(),a=null)}function _(){window.addEventListener("scroll",g),document.addEventListener("click",s),document.addEventListener("keyup",b),document.addEventListener("touchstart",w),document.addEventListener("touchend",s)}function d(){window.removeEventListener("scroll",g),document.removeEventListener("keyup",b),document.removeEventListener("click",s),document.removeEventListener("touchstart",w),document.removeEventListener("touchend",s)}function g(t){l===null&&(l=window.pageYOffset);var u=l-window.pageYOffset;Math.abs(u)>=40&&n()}function b(t){t.keyCode==27&&n()}function s(t){t.stopPropagation(),t.preventDefault(),n()}function w(t){o=t.touches[0].pageY,t.target.addEventListener("touchmove",e)}function e(t){Math.abs(t.touches[0].pageY-o)<=10||(n(),t.target.removeEventListener("touchmove",e))}var a=null,l=null,o=null;return{listen:h}})().listen()}();const q={"data-action":"zoom"},R=O({__name:"ImageZoom",setup(p){return(B,x)=>(W(),H("img",q))}}),D=r("div",{class:"flex items-center mt-8 intro-y"},[r("h2",{class:"mr-auto text-lg font-medium"},"Image Zoom")],-1),F={class:"grid grid-cols-12 gap-6 mt-5"},I={class:"col-span-12 intro-y lg:col-span-6"},Y={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},X=r("h2",{class:"mr-auto text-base font-medium"},"Implementation",-1),V={class:"p-5"},K={class:"leading-relaxed"},Z=r("p",{class:"mb-5"}," Trust fund seitan chia, wolf lomo letterpress Bushwick before they sold out. Carles kogi fixie, squid twee Tonx readymade cred typewriter scenester locavore kale chips vegan organic. Meggings pug wolf Shoreditch typewriter skateboard. McSweeney's iPhone chillwave, food truck direct trade disrupt flannel irony tousled Cosby sweater single-origin coffee. Organic disrupt bicycle rights, tattooed messenger bag flannel craft beer fashion axe bitters. Readymade sartorial craft beer, quinoa sustainable butcher Marfa Echo Park Terry Richardson gluten-free flannel retro cred mlkshk banjo. Salvia 90's art party Blue Bottle, PBR&B cardigan 8-bit. ",-1),G=r("p",{class:"mb-5"}," Meggings irony fashion axe, tattooed master cleanse Blue Bottle stumptown bitters authentic flannel freegan paleo letterpress ugh sriracha. Wolf PBR&B art party aesthetic meh cliche. Sartorial before they sold out deep v, aesthetic PBR&B craft beer post-ironic synth keytar pork belly skateboard pour-over. Tonx cray pug Etsy, gastropub ennui wolf ethnic Odd Future viral master cleanse skateboard banjo. Pitchfork scenester cornhole, whatever try-hard ethnic banjo +1 gastropub American Apparel vinyl skateboard Shoreditch seitan. Blue Bottle keffiyeh Austin Helvetica art party. Portland ethnic fixie, beard retro direct trade ugh scenester Tumblr readymade authentic plaid pickled hashtag biodiesel. ",-1),U={class:"w-full h-64 my-5 image-fit"},$=r("p",{class:"mb-5"}," Thundercats freegan Truffaut, four loko twee Austin scenester lo-fi seitan High Life paleo quinoa cray. Schlitz butcher ethical Tumblr, pop-up DIY keytar ethnic iPhone PBR sriracha. Tonx direct trade bicycle rights gluten-free flexitarian asymmetrical. Whatever drinking vinegar PBR XOXO Bushwick gentrify. Cliche semiotics banjo retro squid Wes Anderson. Fashion axe dreamcatcher you probably haven't heard of them bicycle rights. Tote bag organic four loko ethical selfies gastropub, PBR fingerstache tattooed bicycle rights. ",-1),J=r("p",{class:"mb-5"}," Ugh Portland Austin, distillery tattooed typewriter polaroid pug Banksy Neutra keffiyeh. Shoreditch mixtape wolf PBR&B, tote bag dreamcatcher literally bespoke Odd Future selfies 90's master cleanse vegan. Flannel tofu deep v next level pickled, authentic Etsy Shoreditch literally swag photo booth iPhone pug semiotics banjo. Bicycle rights butcher Blue Bottle, actually DIY semiotics Banksy banjo mixtape Austin pork belly post-ironic. American Apparel gastropub hashtag, McSweeney's master cleanse occupy High Life bitters wayfarers next level bicycle rights. Wolf chia Terry Richardson, pop-up plaid kitsch ugh. Butcher +1 Carles, swag selfies Blue Bottle viral. ",-1),Q=r("p",{class:"mb-5"}," Keffiyeh food truck organic letterpress leggings iPhone four loko hella pour-over occupy, Wes Anderson cray post-ironic. Neutra retro fixie gastropub +1, High Life semiotics. Vinyl distillery Etsy freegan flexitarian cliche jean shorts, Schlitz wayfarers skateboard tousled irony locavore XOXO meh. Ethnic Wes Anderson McSweeney's messenger bag, mixtape XOXO slow-carb cornhole aesthetic Marfa banjo Thundercats bitters. Raw denim banjo typewriter cray Tumblr, High Life single-origin coffee. 90's Tumblr cred, Terry Richardson occupy raw denim tofu fashion axe photo booth banh mi. Trust fund locavore Helvetica, fashion axe selvage authentic Shoreditch swag selfies stumptown +1. ",-1),ee={class:"float-left w-3/5 h-64 mr-6 image-fit"},te=r("p",{class:"mb-5"}," Scenester chambray slow-carb, trust fund biodiesel ugh bicycle rights cornhole. Gentrify messenger bag Truffaut tousled roof party pork belly leggings, photo booth jean shorts. Austin readymade PBR plaid chambray. Squid Echo Park pour-over, wayfarers forage whatever locavore typewriter artisan deep v four loko. Locavore occupy Neutra Pitchfork McSweeney's, wayfarers fingerstache. Actually asymmetrical drinking vinegar yr brunch biodiesel. Before they sold out sustainable readymade craft beer Portland gastropub squid Austin, roof party Thundercats chambray narwhal Bushwick pug. ",-1),ae=r("p",{class:"mb-5"}," Literally typewriter chillwave, bicycle rights Carles flannel wayfarers. Biodiesel farm-to-table actually, locavore keffiyeh hella shabby chic pour-over try-hard Bushwick. Sriracha American Apparel Brooklyn, synth cray stumptown blog Bushwick +1 VHS hashtag. Wolf umami Carles Marfa, 90's food truck Cosby sweater. Fanny pack try-hard keytar pop-up readymade, master cleanse four loko trust fund polaroid salvia. Photo booth kitsch forage chambray, Carles scenester slow-carb lomo cardigan dreamcatcher. Swag asymmetrical leggings, biodiesel Tonx shabby chic ethnic master cleanse freegan. ",-1),re=r("p",null," Raw denim Banksy shabby chic, 8-bit salvia narwhal fashion axe. Ethical Williamsburg four loko, chia kale chips distillery Shoreditch messenger bag swag iPhone Pitchfork. Viral PBR&B single-origin coffee quinoa readymade, ethical chillwave drinking vinegar gluten-free Wes Anderson kitsch Tumblr synth actually bitters. Butcher McSweeney's forage mlkshk kogi fingerstache. Selvage scenester butcher Shoreditch, Carles beard plaid disrupt DIY. Pug readymade selvage retro, Austin salvia vinyl master cleanse flexitarian deep v bicycle rights plaid Terry Richardson mlkshk pour-over. Trust fund try-hard banh mi Brooklyn, 90's Etsy kogi YOLO salvia. ",-1),ne=O({__name:"ImageZoom",setup(p){return(B,x)=>(W(),H(N,null,[D,r("div",F,[r("div",I,[c(i(T),{class:"intro-y box"},{default:f(({toggle:h})=>[r("div",Y,[X,c(i(E),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:f(()=>[c(i(E).Label,{htmlFor:"show-example-1"},{default:f(()=>[C(" Show example code ")]),_:1}),c(i(E).Input,{id:"show-example-1",onClick:h,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),r("div",V,[c(i(T).Panel,null,{default:f(()=>[r("div",K,[Z,G,r("div",U,[c(i(R),{alt:"Midone Tailwind HTML Admin Template",src:i(M)[0].images[0],class:"w-full rounded-md"},null,8,["src"])]),$,J,Q,r("div",ee,[c(i(R),{alt:"Midone Tailwind HTML Admin Template",src:i(M)[0].images[1],class:"w-full rounded-md"},null,8,["src"])]),te,ae,re])]),_:1}),c(i(T).Panel,{type:"source"},{default:f(()=>[c(i(T).Highlight,null,{default:f(()=>[C(z(`
              <div class="leading-relaxed">
                <p class="mb-5">
                  Trust fund seitan chia, wolf lomo letterpress Bushwick before
                  they sold out. Carles kogi fixie, squid twee Tonx readymade
                  cred typewriter scenester locavore kale chips vegan organic.
                  Meggings pug wolf Shoreditch typewriter skateboard.
                  McSweeney's iPhone chillwave, food truck direct trade disrupt
                  flannel irony tousled Cosby sweater single-origin coffee.
                  Organic disrupt bicycle rights, tattooed messenger bag flannel
                  craft beer fashion axe bitters. Readymade sartorial craft
                  beer, quinoa sustainable butcher Marfa Echo Park Terry
                  Richardson gluten-free flannel retro cred mlkshk banjo. Salvia
                  90's art party Blue Bottle, PBR&B cardigan 8-bit.
                </p>
                <p class="mb-5">
                  Meggings irony fashion axe, tattooed master cleanse Blue
                  Bottle stumptown bitters authentic flannel freegan paleo
                  letterpress ugh sriracha. Wolf PBR&B art party aesthetic meh
                  cliche. Sartorial before they sold out deep v, aesthetic PBR&B
                  craft beer post-ironic synth keytar pork belly skateboard
                  pour-over. Tonx cray pug Etsy, gastropub ennui wolf ethnic Odd
                  Future viral master cleanse skateboard banjo. Pitchfork
                  scenester cornhole, whatever try-hard ethnic banjo +1
                  gastropub American Apparel vinyl skateboard Shoreditch seitan.
                  Blue Bottle keffiyeh Austin Helvetica art party. Portland
                  ethnic fixie, beard retro direct trade ugh scenester Tumblr
                  readymade authentic plaid pickled hashtag biodiesel.
                </p>
                <div class="w-full h-64 my-5 image-fit">
                  <img
                    alt="Midone Tailwind HTML Admin Template"
                    :src="fakerData[0].images[0]"
                    class="w-full rounded-md"
                  />
                </div>
                <p class="mb-5">
                  Thundercats freegan Truffaut, four loko twee Austin scenester
                  lo-fi seitan High Life paleo quinoa cray. Schlitz butcher
                  ethical Tumblr, pop-up DIY keytar ethnic iPhone PBR sriracha.
                  Tonx direct trade bicycle rights gluten-free flexitarian
                  asymmetrical. Whatever drinking vinegar PBR XOXO Bushwick
                  gentrify. Cliche semiotics banjo retro squid Wes Anderson.
                  Fashion axe dreamcatcher you probably haven't heard of them
                  bicycle rights. Tote bag organic four loko ethical selfies
                  gastropub, PBR fingerstache tattooed bicycle rights.
                </p>
                <p class="mb-5">
                  Ugh Portland Austin, distillery tattooed typewriter polaroid
                  pug Banksy Neutra keffiyeh. Shoreditch mixtape wolf PBR&B,
                  tote bag dreamcatcher literally bespoke Odd Future selfies
                  90's master cleanse vegan. Flannel tofu deep v next level
                  pickled, authentic Etsy Shoreditch literally swag photo booth
                  iPhone pug semiotics banjo. Bicycle rights butcher Blue
                  Bottle, actually DIY semiotics Banksy banjo mixtape Austin
                  pork belly post-ironic. American Apparel gastropub hashtag,
                  McSweeney's master cleanse occupy High Life bitters wayfarers
                  next level bicycle rights. Wolf chia Terry Richardson, pop-up
                  plaid kitsch ugh. Butcher +1 Carles, swag selfies Blue Bottle
                  viral.
                </p>
                <p class="mb-5">
                  Keffiyeh food truck organic letterpress leggings iPhone four
                  loko hella pour-over occupy, Wes Anderson cray post-ironic.
                  Neutra retro fixie gastropub +1, High Life semiotics. Vinyl
                  distillery Etsy freegan flexitarian cliche jean shorts,
                  Schlitz wayfarers skateboard tousled irony locavore XOXO meh.
                  Ethnic Wes Anderson McSweeney's messenger bag, mixtape XOXO
                  slow-carb cornhole aesthetic Marfa banjo Thundercats bitters.
                  Raw denim banjo typewriter cray Tumblr, High Life
                  single-origin coffee. 90's Tumblr cred, Terry Richardson
                  occupy raw denim tofu fashion axe photo booth banh mi. Trust
                  fund locavore Helvetica, fashion axe selvage authentic
                  Shoreditch swag selfies stumptown +1.
                </p>
                <div class="float-left w-3/5 h-64 mr-6 image-fit">
                  <img
                    alt="Midone Tailwind HTML Admin Template"
                    :src="fakerData[0].images[1]"
                    class="w-full rounded-md"
                  />
                </div>
                <p class="mb-5">
                  Scenester chambray slow-carb, trust fund biodiesel ugh bicycle
                  rights cornhole. Gentrify messenger bag Truffaut tousled roof
                  party pork belly leggings, photo booth jean shorts. Austin
                  readymade PBR plaid chambray. Squid Echo Park pour-over,
                  wayfarers forage whatever locavore typewriter artisan deep v
                  four loko. Locavore occupy Neutra Pitchfork McSweeney's,
                  wayfarers fingerstache. Actually asymmetrical drinking vinegar
                  yr brunch biodiesel. Before they sold out sustainable
                  readymade craft beer Portland gastropub squid Austin, roof
                  party Thundercats chambray narwhal Bushwick pug.
                </p>
                <p class="mb-5">
                  Literally typewriter chillwave, bicycle rights Carles flannel
                  wayfarers. Biodiesel farm-to-table actually, locavore keffiyeh
                  hella shabby chic pour-over try-hard Bushwick. Sriracha
                  American Apparel Brooklyn, synth cray stumptown blog Bushwick
                  +1 VHS hashtag. Wolf umami Carles Marfa, 90's food truck
                  Cosby sweater. Fanny pack try-hard keytar pop-up readymade,
                  master cleanse four loko trust fund polaroid salvia. Photo
                  booth kitsch forage chambray, Carles scenester slow-carb lomo
                  cardigan dreamcatcher. Swag asymmetrical leggings, biodiesel
                  Tonx shabby chic ethnic master cleanse freegan.
                </p>
                <p>
                  Raw denim Banksy shabby chic, 8-bit salvia narwhal fashion
                  axe. Ethical Williamsburg four loko, chia kale chips
                  distillery Shoreditch messenger bag swag iPhone Pitchfork.
                  Viral PBR&B single-origin coffee quinoa readymade, ethical
                  chillwave drinking vinegar gluten-free Wes Anderson kitsch
                  Tumblr synth actually bitters. Butcher McSweeney's forage
                  mlkshk kogi fingerstache. Selvage scenester butcher
                  Shoreditch, Carles beard plaid disrupt DIY. Pug readymade
                  selvage retro, Austin salvia vinyl master cleanse flexitarian
                  deep v bicycle rights plaid Terry Richardson mlkshk pour-over.
                  Trust fund try-hard banh mi Brooklyn, 90's Etsy kogi YOLO
                  salvia.
                </p>
              </div>
              `),1)]),_:1})]),_:1})])]),_:1})])])],64))}});export{ne as default};
