import{P as l}from"./index-9tI8BQBI.js";import{_ as p}from"./Button.vue_vue_type_script_setup_true_lang-qNMd5tCb.js";import{d as v,r as g,v as b,o as w,f as T,J as k,aR as C,aS as S,aT as B,S as N,g as o,h as e,w as s,u as t,F as P,C as i,i as a,l as _,t as c,s as f}from"./main-CeS48KJa.js";import{_ as L}from"./SimpleLineChart1.vue_vue_type_script_setup_true_lang-8z42XySY.js";import"./colors-Lb3ECc1k.js";const x=v({__name:"TippyContent",props:{to:{},refKey:{},options:{}},setup(h){const u=h,r=g(),n=(m,d)=>{C(`[data-tooltip="${d.to}"]`,{plugins:[S],content:m,allowHTML:!0,arrow:B,popperOptions:{modifiers:[{name:"preventOverflow",options:{rootBoundary:"viewport"}}]},animateFill:!1,animation:"shift-away",theme:"light",trigger:"click",...d.options})},y=m=>{if(u.refKey){const d=N(`bind[${u.refKey}]`);d&&d(m)}};return b(()=>{r.value&&(n(r.value,u),y(r.value))}),(m,d)=>(w(),T("div",{ref_key:"tippyRef",ref:r},[k(m.$slots,"default")],512))}}),F=o("div",{class:"flex items-center mt-8 intro-y"},[o("h2",{class:"mr-auto text-lg font-medium"},"Tooltip")],-1),H={class:"grid grid-cols-12 gap-6 mt-5"},I={class:"col-span-12 intro-y lg:col-span-6"},D={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},E=o("h2",{class:"mr-auto text-base font-medium"},"Basic Tooltip",-1),M={class:"p-5"},$={class:"text-center"},R={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},A=o("h2",{class:"mr-auto text-base font-medium"},"On Click Tooltip",-1),O={class:"p-5"},G={class:"text-center"},K={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},U=o("h2",{class:"mr-auto text-base font-medium"},"Light Tooltip",-1),V={class:"p-5"},W={class:"text-center"},j={class:"col-span-12 intro-y lg:col-span-6"},J={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},q=o("h2",{class:"mr-auto text-base font-medium"},"Custom Tooltip Content",-1),z={class:"p-5"},Q={class:"text-center"},X={class:"tooltip-content"},Y={class:"relative flex items-center py-1"},Z={class:"w-12 h-12 image-fit"},tt=["src"],et={class:"ml-4 mr-auto"},ot={class:"font-medium leading-relaxed dark:text-slate-200"},st=o("div",{class:"text-slate-500 dark:text-slate-400"}," TailwindCSS 3+ HTML Admin Template ",-1),lt={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},at=o("h2",{class:"mr-auto text-base font-medium"},"Chart Tooltip",-1),it={class:"p-5"},nt={class:"text-center"},dt={class:"tooltip-content"},ct=o("div",{class:"font-medium dark:text-slate-200"},"Net Worth",-1),rt={class:"flex items-center mt-2 sm:mt-0"},mt=o("div",{class:"flex w-20 mr-2 dark:text-slate-400"},[a(" USP: "),o("span",{class:"ml-auto font-medium text-success"}," +23% ")],-1),pt={class:"w-24 sm:w-32 lg:w-56"},vt=v({__name:"Tooltip",setup(h){return(u,r)=>(w(),T(P,null,[F,o("div",H,[o("div",I,[e(t(l),{class:"intro-y box"},{default:s(({toggle:n})=>[o("div",D,[E,e(t(i),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:s(()=>[e(t(i).Label,{htmlFor:"show-example-1"},{default:s(()=>[a(" Show example code ")]),_:1}),e(t(i).Input,{id:"show-example-1",onClick:n,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",M,[e(t(l).Panel,null,{default:s(()=>[o("div",$,[e(t(_),{as:t(p),variant:"primary",content:"This is awesome tooltip example!"},{default:s(()=>[a(" Show Tooltip ")]),_:1},8,["as"])])]),_:1}),e(t(l).Panel,{type:"source"},{default:s(()=>[e(t(l).Highlight,null,{default:s(()=>[a(c(`
              <div class="text-center">
                <Tippy
                  :as="Button"
                  variant="primary"
                  content="This is awesome tooltip example!"
                >
                  Show Tooltip
                </Tippy>
              </div>
              `))]),_:1})]),_:1})])]),_:1}),e(t(l),{class:"mt-5 intro-y box"},{default:s(({toggle:n})=>[o("div",R,[A,e(t(i),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:s(()=>[e(t(i).Label,{htmlFor:"show-example-2"},{default:s(()=>[a(" Show example code ")]),_:1}),e(t(i).Input,{id:"show-example-2",onClick:n,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",O,[e(t(l).Panel,null,{default:s(()=>[o("div",G,[e(t(_),{as:t(p),variant:"primary",content:"This is awesome tooltip example!",options:{trigger:"click"}},{default:s(()=>[a(" Show Tooltip ")]),_:1},8,["as"])])]),_:1}),e(t(l).Panel,{type:"source"},{default:s(()=>[e(t(l).Highlight,null,{default:s(()=>[a(c(`
              <div class="text-center">
                <Tippy
                  :as="Button"
                  variant="primary"
                  content="This is awesome tooltip example!"
                  :options="{
                    trigger: 'click',
                  }"
                >
                  Show Tooltip
                </Tippy>
              </div>
              `))]),_:1})]),_:1})])]),_:1}),e(t(l),{class:"mt-5 intro-y box"},{default:s(({toggle:n})=>[o("div",K,[U,e(t(i),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:s(()=>[e(t(i).Label,{htmlFor:"show-example-3"},{default:s(()=>[a(" Show example code ")]),_:1}),e(t(i).Input,{id:"show-example-3",onClick:n,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",V,[e(t(l).Panel,null,{default:s(()=>[o("div",W,[e(t(_),{as:t(p),variant:"primary",content:"This is awesome tooltip example!",options:{theme:"light"}},{default:s(()=>[a(" Show Tooltip ")]),_:1},8,["as"])])]),_:1}),e(t(l).Panel,{type:"source"},{default:s(()=>[e(t(l).Highlight,null,{default:s(()=>[a(c(`
              <div class="text-center">
                <Tippy
                  :as="Button"
                  variant="primary"
                  content="This is awesome tooltip example!"
                  :options="{
                    theme: 'light',
                  }"
                >
                  Show Tooltip
                </Tippy>
              </div>
              `))]),_:1})]),_:1})])]),_:1})]),o("div",j,[e(t(l),{class:"intro-y box"},{default:s(({toggle:n})=>[o("div",J,[q,e(t(i),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:s(()=>[e(t(i).Label,{htmlFor:"show-example-4"},{default:s(()=>[a(" Show example code ")]),_:1}),e(t(i).Input,{id:"show-example-4",onClick:n,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",z,[e(t(l).Panel,null,{default:s(()=>[o("div",Q,[e(t(p),{variant:"primary","data-tooltip":"custom-tooltip-content"},{default:s(()=>[a(" Show Tooltip ")]),_:1})]),o("div",X,[e(t(x),{to:"custom-tooltip-content"},{default:s(()=>[o("div",Y,[o("div",Z,[o("img",{alt:"Midone Tailwind HTML Admin Template",class:"rounded-full",src:t(f)[0].photos[0]},null,8,tt)]),o("div",et,[o("div",ot,c(t(f)[0].users[0].name),1),st])])]),_:1})])]),_:1}),e(t(l).Panel,{type:"source"},{default:s(()=>[e(t(l).Highlight,null,{default:s(()=>[a(c(`
              <!-- BEGIN: Custom Tooltip Toggle -->
              <div class="text-center">
                <Button variant="primary" data-tooltip="custom-tooltip-content">
                  Show Tooltip
                </Button>
              </div>
              <!-- END: Custom Tooltip Toggle -->
              <!-- BEGIN: Custom Tooltip Content -->
              <div class="tooltip-content">
                <TippyContent to="custom-tooltip-content">
                  <div class="relative flex items-center py-1">
                    <div class="w-12 h-12 image-fit">
                      <img
                        alt="Midone Tailwind HTML Admin Template"
                        class="rounded-full"
                        :src="fakerData[0].photos[0]"
                      />
                    </div>
                    <div class="ml-4 mr-auto">
                      <div
                        class="font-medium leading-relaxed dark:text-slate-200"
                      >
                        {{ fakerData[0].users[0].name }}
                      </div>
                      <div class="text-slate-500 dark:text-slate-400">
                        TailwindCSS 3+ HTML Admin Template
                      </div>
                    </div>
                  </div>
                </TippyContent>
              </div>
              <!-- END: Custom Tooltip Content -->
              `),1)]),_:1})]),_:1})])]),_:1}),e(t(l),{class:"mt-5 intro-y box"},{default:s(({toggle:n})=>[o("div",lt,[at,e(t(i),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:s(()=>[e(t(i).Label,{htmlFor:"show-example-5"},{default:s(()=>[a(" Show example code ")]),_:1}),e(t(i).Input,{id:"show-example-5",onClick:n,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",it,[e(t(l).Panel,null,{default:s(()=>[o("div",nt,[e(t(p),{variant:"primary","data-tooltip":"chart-tooltip"},{default:s(()=>[a(" Show Tooltip ")]),_:1})]),o("div",dt,[e(t(x),{to:"chart-tooltip",class:"py-1"},{default:s(()=>[ct,o("div",rt,[mt,o("div",pt,[e(t(L),{height:30})])])]),_:1})])]),_:1}),e(t(l).Panel,{type:"source"},{default:s(()=>[e(t(l).Highlight,null,{default:s(()=>[a(c(`
              <!-- BEGIN: Custom Tooltip Toggle -->
              <div class="text-center">
                <Button variant="primary" data-tooltip="chart-tooltip">
                  Show Tooltip
                </Button>
              </div>
              <!-- END: Custom Tooltip Toggle -->
              <!-- BEGIN: Custom Tooltip Content -->
              <div class="tooltip-content">
                <TippyContent to="chart-tooltip" class="py-1">
                  <div class="font-medium dark:text-slate-200">Net Worth</div>
                  <div class="flex items-center mt-2 sm:mt-0">
                    <div class="flex w-20 mr-2 dark:text-slate-400">
                      USP:
                      <span class="ml-auto font-medium text-success">
                        +23%
                      </span>
                    </div>
                    <div class="w-24 sm:w-32 lg:w-56">
                      <SimpleLineChart1 :height="30" />
                    </div>
                  </div>
                </TippyContent>
              </div>
              <!-- END: Custom Tooltip Content -->
              `))]),_:1})]),_:1})])]),_:1})])])],64))}});export{vt as default};
