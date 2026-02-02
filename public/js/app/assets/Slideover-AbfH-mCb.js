import{P as d}from"./index-9tI8BQBI.js";import{d as G,r as c,f as W,g as o,h as e,w as t,u as l,F as $,o as J,C as v,i,aQ as r,t as p,_ as f,T as w,m as h}from"./main-CeS48KJa.js";import{_ as S}from"./FormLabel.vue_vue_type_script_setup_true_lang-9pIbqFce.js";import{_ as X}from"./FormSelect.vue_vue_type_script_setup_true_lang-rccilgeM.js";import{_ as m}from"./Button.vue_vue_type_script_setup_true_lang-qNMd5tCb.js";const V=o("div",{class:"flex items-center mt-8 intro-y"},[o("h2",{class:"mr-auto text-lg font-medium"},"Slide Over")],-1),j={class:"grid grid-cols-12 gap-6 mt-5"},Q={class:"col-span-12 intro-y lg:col-span-6"},q={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},A=o("h2",{class:"mr-auto text-base font-medium"},"Blank Slide Over",-1),K={id:"blank-slideover",class:"p-5"},R={class:"text-center"},U=o("h2",{class:"mr-auto text-base font-medium"}," Blank Slide Over ",-1),Y={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},Z=o("h2",{class:"mr-auto text-base font-medium"},"Slide Over Size",-1),ee={id:"slideover-size",class:"p-5"},le={class:"text-center"},te=o("h2",{class:"mr-auto text-base font-medium"}," Small Slide Over ",-1),oe=o("h2",{class:"mr-auto text-base font-medium"}," Medium Slide Over ",-1),ae=o("h2",{class:"mr-auto text-base font-medium"}," Large Slide Over ",-1),ie=o("h2",{class:"mr-auto text-base font-medium"}," Superlarge Slide Over ",-1),re={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},se=o("h2",{class:"mr-auto text-base font-medium"}," Slide Over With Close Button ",-1),de={id:"button-slideover",class:"p-5"},ne={class:"text-center"},ve=o("h2",{class:"mr-auto text-base font-medium"}," Slide Over With Close Button ",-1),me={class:"col-span-12 intro-y lg:col-span-6"},ue={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},ce=o("h2",{class:"mr-auto text-base font-medium"},"Overlapping Slide Over",-1),pe={id:"overlapping-slideover",class:"p-5"},Se={class:"text-center"},fe=o("h2",{class:"mr-auto text-base font-medium"}," Overlapping Slide Over ",-1),he={class:"text-center"},_e=o("div",{class:"mb-5"}," Click button bellow to show overlapping slide over! ",-1),ge=o("h2",{class:"mr-auto text-base font-medium"}," Overlapping Slide Over ",-1),we={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},xe=o("h2",{class:"mr-auto text-base font-medium"}," Header & Footer Slide Over ",-1),be={id:"header-footer-slideover",class:"p-5"},Oe={class:"text-center"},ye=o("h2",{class:"mr-auto text-base font-medium"}," Broadcast Message ",-1),Pe={class:"mt-3"},De={class:"mt-3"},ke={class:"mt-3"},Be={class:"mt-3"},Te={class:"mt-3"},Ce=o("option",null,"10",-1),Ee=o("option",null,"25",-1),Fe=o("option",null,"35",-1),Ne=o("option",null,"50",-1),Ie={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},Me=o("h2",{class:"mr-auto text-base font-medium"}," Programmatically Show/Hide Slide Over ",-1),Le={id:"programmatically-show-hide-slideover",class:"p-5"},ze={class:"text-center"},He=o("h2",{class:"mr-auto text-base font-medium"}," Programmatically Show/Hide Slide Over ",-1),je=G({__name:"Slideover",setup(Ge){const O=c(!1),y=n=>{O.value=n},P=c(!1),D=n=>{P.value=n},k=c(!1),B=n=>{k.value=n},T=c(!1),C=n=>{T.value=n},E=c(!1),F=n=>{E.value=n},x=c(!1),_=n=>{x.value=n},N=c(!1),b=n=>{N.value=n},I=c(!1),M=n=>{I.value=n},L=c(!1),z=n=>{L.value=n},H=c(!1),g=n=>{H.value=n};return(n,a)=>(J(),W($,null,[V,o("div",j,[o("div",Q,[e(l(d),{class:"intro-y box"},{default:t(({toggle:u})=>[o("div",q,[A,e(l(v),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:t(()=>[e(l(v).Label,{htmlFor:"show-example-1"},{default:t(()=>[i(" Show example code ")]),_:1}),e(l(v).Input,{id:"show-example-1",onClick:u,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",K,[e(l(d).Panel,null,{default:t(()=>[o("div",R,[e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[0]||(a[0]=s=>{s.preventDefault(),y(!0)})},{default:t(()=>[i(" Show Slide Over ")]),_:1})]),e(l(r),{open:O.value,onClose:a[1]||(a[1]=()=>{y(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[U]),_:1}),e(l(r).Description,null,{default:t(()=>[i(" This is totally awesome blank slide over! ")]),_:1})]),_:1})]),_:1},8,["open"])]),_:1}),e(l(d).Panel,{type:"source"},{default:t(()=>[e(l(d).Highlight,null,{default:t(()=>[i(p(`
              <!-- BEGIN: Slide Over Toggle -->
              <div class="text-center">
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setBasicSlideoverPreview(true);
                    }"
                >
                  Show Slide Over
                </Button>
              </div>
              <!-- END: Slide Over Toggle -->
              <!-- BEGIN: Slide Over Content -->
              <Slideover
                :open="basicSlideoverPreview"
                @close="
                  () => {
                    setBasicSlideoverPreview(false);
                  }
                "
              >
                <Slideover.Panel>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Blank Slide Over
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description>
                    This is totally awesome blank slide over!
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Slide Over Content -->
              `),1)]),_:1})]),_:1})])]),_:1}),e(l(d),{class:"mt-5 intro-y box"},{default:t(({toggle:u})=>[o("div",Y,[Z,e(l(v),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:t(()=>[e(l(v).Label,{htmlFor:"show-example-2"},{default:t(()=>[i(" Show example code ")]),_:1}),e(l(v).Input,{id:"show-example-2",onClick:u,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",ee,[e(l(d).Panel,null,{default:t(()=>[o("div",le,[e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[2]||(a[2]=s=>{s.preventDefault(),D(!0)}),class:"mb-2 mr-1"},{default:t(()=>[i(" Show Small Slide Over ")]),_:1}),e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[3]||(a[3]=s=>{s.preventDefault(),B(!0)}),class:"mb-2 mr-1"},{default:t(()=>[i(" Show Medium Slide Over ")]),_:1}),e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[4]||(a[4]=s=>{s.preventDefault(),C(!0)}),class:"mb-2 mr-1"},{default:t(()=>[i(" Show Large Slide Over ")]),_:1}),e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[5]||(a[5]=s=>{s.preventDefault(),F(!0)}),class:"mb-2 mr-1"},{default:t(()=>[i(" Show Superlarge Slide Over ")]),_:1})]),e(l(r),{size:"sm",open:P.value,onClose:a[6]||(a[6]=()=>{D(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[te]),_:1}),e(l(r).Description,null,{default:t(()=>[i(" This is totally awesome small slide over! ")]),_:1})]),_:1})]),_:1},8,["open"]),e(l(r),{open:k.value,onClose:a[7]||(a[7]=()=>{B(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[oe]),_:1}),e(l(r).Description,null,{default:t(()=>[i(" This is totally awesome medium slide over! ")]),_:1})]),_:1})]),_:1},8,["open"]),e(l(r),{size:"lg",open:T.value,onClose:a[8]||(a[8]=()=>{C(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[ae]),_:1}),e(l(r).Description,null,{default:t(()=>[i(" This is totally awesome large slide over! ")]),_:1})]),_:1})]),_:1},8,["open"]),e(l(r),{size:"xl",open:E.value,onClose:a[9]||(a[9]=()=>{F(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[ie]),_:1}),e(l(r).Description,null,{default:t(()=>[i(" This is totally awesome superlarge slide over! ")]),_:1})]),_:1})]),_:1},8,["open"])]),_:1}),e(l(d).Panel,{type:"source"},{default:t(()=>[e(l(d).Highlight,null,{default:t(()=>[i(p(`
              <div class="text-center">
                <!-- BEGIN: Small Slide Over Toggle -->
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setSmallSlideoverSizePreview(true);
                    }"
                  class="mb-2 mr-1"
                >
                  Show Small Slide Over
                </Button>
                <!-- END: Small Slide Over Toggle -->
                <!-- BEGIN: Medium Slide Over Toggle -->
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setMediumSlideoverSizePreview(true);
                    }"
                  class="mb-2 mr-1"
                >
                  Show Medium Slide Over
                </Button>
                <!-- END: Medium Slide Over Toggle -->
                <!-- BEGIN: Large Slide Over Toggle -->
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setLargeSlideoverSizePreview(true);
                    }"
                  class="mb-2 mr-1"
                >
                  Show Large Slide Over
                </Button>
                <!-- END: Large Slide Over Toggle -->
                <!-- BEGIN: Super Large Slide Over Toggle -->
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setSuperlargeSlideoverSizePreview(true);
                    }"
                  class="mb-2 mr-1"
                >
                  Show Superlarge Slide Over
                </Button>
                <!-- END: Super Large Slide Over Toggle -->
              </div>
              <!-- BEGIN: Small Slide Over Content -->
              <Slideover
                size="sm"
                :open="smallSlideoverSizePreview"
                @close="
                  () => {
                    setSmallSlideoverSizePreview(false);
                  }
                "
              >
                <Slideover.Panel>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Small Slide Over
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description>
                    This is totally awesome small slide over!
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Small Slide Over Content -->
              <!-- BEGIN: Medium Slide Over Content -->
              <Slideover
                :open="mediumSlideoverSizePreview"
                @close="
                  () => {
                    setMediumSlideoverSizePreview(false);
                  }
                "
              >
                <Slideover.Panel>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Medium Slide Over
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description>
                    This is totally awesome medium slide over!
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Medium Slide Over Content -->
              <!-- BEGIN: Large Slide Over Content -->
              <Slideover
                size="lg"
                :open="largeSlideoverSizePreview"
                @close="
                  () => {
                    setLargeSlideoverSizePreview(false);
                  }
                "
              >
                <Slideover.Panel>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Large Slide Over
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description>
                    This is totally awesome large slide over!
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Large Slide Over Content -->
              <!-- BEGIN: Super Large Slide Over Content -->
              <Slideover
                size="xl"
                :open="superlargeSlideoverSizePreview"
                @close="
                  () => {
                    setSuperlargeSlideoverSizePreview(false);
                  }
                "
              >
                <Slideover.Panel>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Superlarge Slide Over
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description>
                    This is totally awesome superlarge slide over!
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Super Large Slide Over Content -->
              `),1)]),_:1})]),_:1})])]),_:1}),e(l(d),{class:"mt-5 intro-y box"},{default:t(({toggle:u})=>[o("div",re,[se,e(l(v),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:t(()=>[e(l(v).Label,{htmlFor:"show-example-3"},{default:t(()=>[i(" Show example code ")]),_:1}),e(l(v).Input,{id:"show-example-3",onClick:u,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",de,[e(l(d).Panel,null,{default:t(()=>[o("div",ne,[e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[10]||(a[10]=s=>{s.preventDefault(),b(!0)})},{default:t(()=>[i(" Show Slide Over ")]),_:1})]),e(l(r),{backdrop:"static",open:N.value,onClose:a[12]||(a[12]=()=>{b(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[o("a",{onClick:a[11]||(a[11]=s=>{s.preventDefault(),b(!1)}),class:"absolute top-0 left-0 right-auto mt-4 -ml-12",href:"#"},[e(l(f),{icon:"X",class:"w-8 h-8 text-slate-400"})]),e(l(r).Title,{class:"p-5"},{default:t(()=>[ve]),_:1}),e(l(r).Description,null,{default:t(()=>[i(" This is totally awesome slide over with close button! ")]),_:1})]),_:1})]),_:1},8,["open"])]),_:1}),e(l(d).Panel,{type:"source"},{default:t(()=>[e(l(d).Highlight,null,{default:t(()=>[i(p(`
              <!-- BEGIN: Modal Toggle -->
              <div class="text-center">
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setButtonSlideoverPreview(true);
                    }"
                >
                  Show Slide Over
                </Button>
              </div>
              <!-- END: Modal Toggle -->
              <!-- BEGIN: Modal Content -->
              <Slideover
                backdrop="static"
                :open="buttonSlideoverPreview"
                @close="
                  () => {
                    setButtonSlideoverPreview(false);
                  }
                "
              >
                <Slideover.Panel>
                  <a
                    @click="(event: MouseEvent) => {
                        event.preventDefault();
                        setButtonSlideoverPreview(false);
                      }"
                    class="absolute top-0 left-0 right-auto mt-4 -ml-12"
                    href="#"
                  >
                    <Lucide icon="X" class="w-8 h-8 text-slate-400" />
                  </a>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Slide Over With Close Button
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description>
                    This is totally awesome slide over with close button!
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Modal Content -->
              `),1)]),_:1})]),_:1})])]),_:1})]),o("div",me,[e(l(d),{class:"intro-y box"},{default:t(({toggle:u})=>[o("div",ue,[ce,e(l(v),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:t(()=>[e(l(v).Label,{htmlFor:"show-example-4"},{default:t(()=>[i(" Show example code ")]),_:1}),e(l(v).Input,{id:"show-example-4",onClick:u,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",pe,[e(l(d).Panel,null,{default:t(()=>[o("div",Se,[e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[13]||(a[13]=s=>{s.preventDefault(),M(!0)})},{default:t(()=>[i(" Show Slide Over ")]),_:1})]),e(l(r),{open:I.value,onClose:a[16]||(a[16]=()=>{M(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[fe]),_:1}),e(l(r).Description,{class:"px-5 py-10"},{default:t(()=>[o("div",he,[_e,e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[14]||(a[14]=s=>{s.preventDefault(),z(!0)})},{default:t(()=>[i(" Show Overlapping Slide Over ")]),_:1}),e(l(r),{open:L.value,onClose:a[15]||(a[15]=()=>{z(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[ge]),_:1}),e(l(r).Description,{class:"text-center"},{default:t(()=>[i(" This is totally awesome overlapping slide over! ")]),_:1})]),_:1})]),_:1},8,["open"])])]),_:1})]),_:1})]),_:1},8,["open"])]),_:1}),e(l(d).Panel,{type:"source"},{default:t(()=>[e(l(d).Highlight,null,{default:t(()=>[i(p(`
              <!-- BEGIN: Slide Over Toggle -->
              <div class="text-center">
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setOverlappingSlideoverPreview(true);
                    }"
                >
                  Show Slide Over
                </Button>
              </div>
              <!-- END: Slide Over Toggle -->
              <!-- BEGIN: Slide Over Content -->
              <Slideover
                :open="overlappingSlideoverPreview"
                @close="
                  () => {
                    setOverlappingSlideoverPreview(false);
                  }
                "
              >
                <Slideover.Panel>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Overlapping Slide Over
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description class="px-5 py-10">
                    <div class="text-center">
                      <div class="mb-5">
                        Click button bellow to show overlapping slide over!
                      </div>
                      <!-- BEGIN: Overlapping Slide Over Toggle -->
                      <Button
                        as="a"
                        href="#"
                        variant="primary"
                        @click="(event: MouseEvent) => {
                            event.preventDefault();
                            setNextOverlappingSlideoverPreview(true);
                          }"
                      >
                        Show Overlapping Slide Over
                      </Button>
                      <!-- END: Overlapping Slide Over Toggle -->
                      <!-- BEGIN: Overlapping Slide Over Content -->
                      <Slideover
                        :open="nextOverlappingSlideoverPreview"
                        @close="
                          () => {
                            setNextOverlappingSlideoverPreview(false);
                          }
                        "
                      >
                        <Slideover.Panel>
                          <Slideover.Title class="p-5">
                            <h2 class="mr-auto text-base font-medium">
                              Overlapping Slide Over
                            </h2>
                          </Slideover.Title>
                          <Slideover.Description class="text-center">
                            This is totally awesome overlapping slide over!
                          </Slideover.Description>
                        </Slideover.Panel>
                      </Slideover>
                      <!-- END: Overlapping Slide Over Content -->
                    </div>
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Slide Over Content -->
              `),1)]),_:1})]),_:1})])]),_:1}),e(l(d),{class:"mt-5 intro-y box"},{default:t(({toggle:u})=>[o("div",we,[xe,e(l(v),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:t(()=>[e(l(v).Label,{htmlFor:"show-example-5"},{default:t(()=>[i(" Show example code ")]),_:1}),e(l(v).Input,{id:"show-example-5",onClick:u,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",be,[e(l(d).Panel,null,{default:t(()=>[o("div",Oe,[e(l(m),{as:"a",href:"#",variant:"primary",onClick:a[17]||(a[17]=s=>{s.preventDefault(),g(!0)})},{default:t(()=>[i(" Show Slide Over ")]),_:1})]),e(l(r),{backdrop:"static",open:H.value,onClose:a[20]||(a[20]=()=>{g(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[o("a",{onClick:a[18]||(a[18]=s=>{s.preventDefault(),g(!1)}),class:"absolute top-0 left-0 right-auto mt-4 -ml-12",href:"#"},[e(l(f),{icon:"X",class:"w-8 h-8 text-slate-400"})]),e(l(r).Title,null,{default:t(()=>[ye,e(l(m),{variant:"outline-secondary",class:"hidden sm:flex"},{default:t(()=>[e(l(f),{icon:"File",class:"w-4 h-4 mr-2"}),i(" Download Docs ")]),_:1}),e(l(w),{class:"sm:hidden"},{default:t(()=>[e(l(w).Button,{as:"a",class:"block w-5 h-5",href:"#"},{default:t(()=>[e(l(f),{icon:"MoreHorizontal",class:"w-5 h-5 text-slate-500"})]),_:1}),e(l(w).Items,{class:"w-40"},{default:t(()=>[e(l(w).Item,null,{default:t(()=>[e(l(f),{icon:"File",class:"w-4 h-4 mr-2"}),i(" Download Docs ")]),_:1})]),_:1})]),_:1})]),_:1}),e(l(r).Description,null,{default:t(()=>[o("div",null,[e(l(S),{htmlFor:"modal-form-1"},{default:t(()=>[i("From")]),_:1}),e(l(h),{id:"modal-form-1",type:"text",placeholder:"example@gmail.com"})]),o("div",Pe,[e(l(S),{htmlFor:"modal-form-2"},{default:t(()=>[i("To")]),_:1}),e(l(h),{id:"modal-form-2",type:"text",placeholder:"example@gmail.com"})]),o("div",De,[e(l(S),{htmlFor:"modal-form-3"},{default:t(()=>[i(" Subject ")]),_:1}),e(l(h),{id:"modal-form-3",type:"text",placeholder:"Important Meeting"})]),o("div",ke,[e(l(S),{htmlFor:"modal-form-4"},{default:t(()=>[i(" Has the Words ")]),_:1}),e(l(h),{id:"modal-form-4",type:"text",placeholder:"Job, Work, Documentation"})]),o("div",Be,[e(l(S),{htmlFor:"modal-form-5"},{default:t(()=>[i(" Doesn't Have ")]),_:1}),e(l(h),{id:"modal-form-5",type:"text",placeholder:"Job, Work, Documentation"})]),o("div",Te,[e(l(S),{htmlFor:"modal-form-6"},{default:t(()=>[i("Size")]),_:1}),e(l(X),{id:"modal-form-6"},{default:t(()=>[Ce,Ee,Fe,Ne]),_:1})])]),_:1}),e(l(r).Footer,null,{default:t(()=>[e(l(m),{variant:"outline-secondary",type:"button",onClick:a[19]||(a[19]=()=>{g(!1)}),class:"w-20 mr-1"},{default:t(()=>[i(" Cancel ")]),_:1}),e(l(m),{variant:"primary",type:"button",class:"w-20"},{default:t(()=>[i(" Send ")]),_:1})]),_:1})]),_:1})]),_:1},8,["open"])]),_:1}),e(l(d).Panel,{type:"source"},{default:t(()=>[e(l(d).Highlight,null,{default:t(()=>[i(p(`
              <!-- BEGIN: Slide Over Toggle -->
              <div class="text-center">
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setHeaderFooterSlideoverPreview(true);
                    }"
                >
                  Show Slide Over
                </Button>
              </div>
              <!-- END: Slide Over Toggle -->
              <!-- BEGIN: Slide Over Content -->
              <Slideover
                backdrop="static"
                :open="headerFooterSlideoverPreview"
                @close="
                  () => {
                    setHeaderFooterSlideoverPreview(false);
                  }
                "
              >
                <!-- BEGIN: Slide Over Header -->
                <Slideover.Panel>
                  <a
                    @click="(event: MouseEvent) => {
                        event.preventDefault();
                        setHeaderFooterSlideoverPreview(false);
                      }"
                    class="absolute top-0 left-0 right-auto mt-4 -ml-12"
                    href="#"
                  >
                    <Lucide icon="X" class="w-8 h-8 text-slate-400" />
                  </a>
                  <Slideover.Title>
                    <h2 class="mr-auto text-base font-medium">
                      Broadcast Message
                    </h2>
                    <Button variant="outline-secondary" class="hidden sm:flex">
                      <Lucide icon="File" class="w-4 h-4 mr-2" />
                      Download Docs
                    </Button>
                    <Menu class="sm:hidden">
                      <Menu.Button as="a" class="block w-5 h-5" href="#">
                        <Lucide
                          icon="MoreHorizontal"
                          class="w-5 h-5 text-slate-500"
                        />
                      </Menu.Button>
                      <Menu.Items class="w-40">
                        <Menu.Item>
                          <Lucide icon="File" class="w-4 h-4 mr-2" />
                          Download Docs
                        </Menu.Item>
                      </Menu.Items>
                    </Menu>
                  </Slideover.Title>
                  <!-- END: Slide Over Header -->
                  <!-- BEGIN: Slide Over Body -->
                  <Slideover.Description>
                    <div>
                      <FormLabel htmlFor="modal-form-1"> From </FormLabel>
                      <FormInput
                        id="modal-form-1"
                        type="text"
                        placeholder="example@gmail.com"
                      />
                    </div>
                    <div class="mt-3">
                      <FormLabel htmlFor="modal-form-2"> To </FormLabel>
                      <FormInput
                        id="modal-form-2"
                        type="text"
                        placeholder="example@gmail.com"
                      />
                    </div>
                    <div class="mt-3">
                      <FormLabel htmlFor="modal-form-3"> Subject </FormLabel>
                      <FormInput
                        id="modal-form-3"
                        type="text"
                        placeholder="Important Meeting"
                      />
                    </div>
                    <div class="mt-3">
                      <FormLabel htmlFor="modal-form-4">
                        Has the Words
                      </FormLabel>
                      <FormInput
                        id="modal-form-4"
                        type="text"
                        placeholder="Job, Work, Documentation"
                      />
                    </div>
                    <div class="mt-3">
                      <FormLabel htmlFor="modal-form-5">
                        Doesn't Have
                      </FormLabel>
                      <FormInput
                        id="modal-form-5"
                        type="text"
                        placeholder="Job, Work, Documentation"
                      />
                    </div>
                    <div class="mt-3">
                      <FormLabel htmlFor="modal-form-6"> Size </FormLabel>
                      <FormSelect id="modal-form-6">
                        <option>10</option>
                        <option>25</option>
                        <option>35</option>
                        <option>50</option>
                      </FormSelect>
                    </div>
                  </Slideover.Description>
                  <!-- END: Slide Over Body -->
                  <!-- BEGIN: Slide Over Footer -->
                  <Slideover.Footer>
                    <Button
                      variant="outline-secondary"
                      type="button"
                      @click="
                        () => {
                          setHeaderFooterSlideoverPreview(false);
                        }
                      "
                      class="w-20 mr-1"
                    >
                      Cancel
                    </Button>
                    <Button variant="primary" type="button" class="w-20">
                      Send
                    </Button>
                  </Slideover.Footer>
                </Slideover.Panel>
                <!-- END: Slide Over Footer -->
              </Slideover>
              <!-- END: Slide Over Content -->
              `),1)]),_:1})]),_:1})])]),_:1}),e(l(d),{class:"mt-5 intro-y box"},{default:t(({toggle:u})=>[o("div",Ie,[Me,e(l(v),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:t(()=>[e(l(v).Label,{htmlFor:"show-example-6"},{default:t(()=>[i(" Show example code ")]),_:1}),e(l(v).Input,{id:"show-example-6",onClick:u,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",Le,[e(l(d).Panel,null,{default:t(()=>[o("div",ze,[e(l(m),{as:"a",id:"programmatically-show-slideover",href:"#",variant:"primary",class:"mb-2 mr-1",onClick:a[21]||(a[21]=s=>{s.preventDefault(),_(!0)})},{default:t(()=>[i(" Show Slide Over ")]),_:1})]),e(l(r),{open:x.value,onClose:a[24]||(a[24]=()=>{_(!1)})},{default:t(()=>[e(l(r).Panel,null,{default:t(()=>[e(l(r).Title,{class:"p-5"},{default:t(()=>[He]),_:1}),e(l(r).Description,{class:"p-10 text-center"},{default:t(()=>[e(l(m),{as:"a",id:"programmatically-hide-slideover",href:"#",variant:"primary",class:"mr-1",onClick:a[22]||(a[22]=s=>{s.preventDefault(),_(!1)})},{default:t(()=>[i(" Hide Slide Over ")]),_:1}),e(l(m),{as:"a",id:"programmatically-toggle-slideover",href:"#",variant:"primary",class:"mt-2 mr-1 sm:mt-0",onClick:a[23]||(a[23]=s=>{s.preventDefault(),_(!x.value)})},{default:t(()=>[i(" Toggle Slide Over ")]),_:1})]),_:1})]),_:1})]),_:1},8,["open"])]),_:1}),e(l(d).Panel,{type:"source"},{default:t(()=>[e(l(d).Highlight,null,{default:t(()=>[i(p(`
              <!-- BEGIN: Show Slide Over Toggle -->
              <div class="text-center">
                <Button
                  as="a"
                  id="programmatically-show-slideover"
                  href="#"
                  variant="primary"
                  class="mb-2 mr-1"
                  @click="(event: MouseEvent) => {
                      event.preventDefault();
                      setProgrammaticallySlideover(true);
                    }"
                >
                  Show Slide Over
                </Button>
              </div>
              <!-- END: Show Slide Over Toggle -->
              <!-- BEGIN: Slide Over Content -->
              <Slideover
                :open="programmaticallySlideover"
                @close="
                  () => {
                    setProgrammaticallySlideover(false);
                  }
                "
              >
                <Slideover.Panel>
                  <Slideover.Title class="p-5">
                    <h2 class="mr-auto text-base font-medium">
                      Programmatically Show/Hide Slide Over
                    </h2>
                  </Slideover.Title>
                  <Slideover.Description class="p-10 text-center">
                    <!-- BEGIN: Hide Slide Over Toggle -->
                    <Button
                      as="a"
                      id="programmatically-hide-slideover"
                      href="#"
                      variant="primary"
                      class="mr-1"
                      @click="(event: MouseEvent) => {
                          event.preventDefault();
                          setProgrammaticallySlideover(false);
                        }"
                    >
                      Hide Slide Over
                    </Button>
                    <!-- END: Hide Slide Over Toggle -->
                    <!-- BEGIN: Toggle Slide Over Toggle -->
                    <Button
                      as="a"
                      id="programmatically-toggle-slideover"
                      href="#"
                      variant="primary"
                      class="mt-2 mr-1 sm:mt-0"
                      @click="(event: MouseEvent) => {
                          event.preventDefault();
                          setProgrammaticallySlideover(
                            !programmaticallySlideover
                          );
                        }"
                    >
                      Toggle Slide Over
                    </Button>
                    <!-- END: Toggle Slide Over Toggle -->
                  </Slideover.Description>
                </Slideover.Panel>
              </Slideover>
              <!-- END: Slide Over Content -->
              `),1)]),_:1})]),_:1})])]),_:1})])])],64))}});export{je as default};
