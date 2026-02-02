import{P as s}from"./index-9tI8BQBI.js";import{d as D,r as p,f as M,g as t,h as e,w as a,u as l,F,o as B,C as n,i as r,t as f,_ as h,D as m,T as _}from"./main-CeS48KJa.js";import{_ as g}from"./FormLabel.vue_vue_type_script_setup_true_lang-9pIbqFce.js";import{_ as c}from"./Litepicker.vue_vue_type_script_setup_true_lang-qhqsZLGA.js";import{_ as w}from"./Button.vue_vue_type_script_setup_true_lang-qNMd5tCb.js";const C=t("div",{class:"flex items-center mt-8 intro-y"},[t("h2",{class:"mr-auto text-lg font-medium"},"Datepicker")],-1),N={class:"grid grid-cols-12 gap-6 mt-5 intro-y"},P={class:"col-span-12 lg:col-span-6"},Y={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},V=t("h2",{class:"mr-auto text-base font-medium"},"Basic Date Picker",-1),L={class:"p-5"},I={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},E=t("h2",{class:"mr-auto text-base font-medium"},"Input Group",-1),S={class:"p-5"},A={class:"relative w-56 mx-auto"},T={class:"absolute flex items-center justify-center w-10 h-full border rounded-l bg-slate-100 text-slate-500 dark:bg-darkmode-700 dark:border-darkmode-800 dark:text-slate-400"},W={class:"col-span-12 lg:col-span-6"},H={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},G=t("h2",{class:"mr-auto text-base font-medium"},"Date Range Picker",-1),R={class:"p-5"},U={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},O=t("h2",{class:"mr-auto text-base font-medium"},"Modal Date Picker",-1),$={class:"p-5"},j={class:"text-center"},z=t("h2",{class:"mr-auto text-base font-medium"},"Filter by Date",-1),q={class:"col-span-12 sm:col-span-6"},J={class:"col-span-12 sm:col-span-6"},ae=D({__name:"Datepicker",setup(K){const u=p(""),k=p(""),x=p(!1),b=y=>{x.value=y},v=p(null);return(y,o)=>(B(),M(F,null,[C,t("div",N,[t("div",P,[e(l(s),{class:"intro-y box"},{default:a(({toggle:i})=>[t("div",Y,[V,e(l(n),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:a(()=>[e(l(n).Label,{htmlFor:"show-example-1"},{default:a(()=>[r(" Show example code ")]),_:1}),e(l(n).Input,{id:"show-example-1",onClick:i,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),t("div",L,[e(l(s).Panel,null,{default:a(()=>[e(l(c),{modelValue:u.value,"onUpdate:modelValue":o[0]||(o[0]=d=>u.value=d),options:{autoApply:!1,showWeekNumbers:!0,dropdowns:{minYear:1990,maxYear:null,months:!0,years:!0}},class:"block w-56 mx-auto"},null,8,["modelValue"])]),_:1}),e(l(s).Panel,{type:"source"},{default:a(()=>[e(l(s).Highlight,null,{default:a(()=>[r(f(`
              <Litepicker
                v-model="date"
                :options="{
                  autoApply: false,
                  showWeekNumbers: true,
                  dropdowns: {
                    minYear: 1990,
                    maxYear: null,
                    months: true,
                    years: true,
                  },
                }"
                class="block w-56 mx-auto"
              />
              `))]),_:1})]),_:1})])]),_:1}),e(l(s),{class:"mt-5 intro-y box"},{default:a(({toggle:i})=>[t("div",I,[E,e(l(n),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:a(()=>[e(l(n).Label,{htmlFor:"show-example-2"},{default:a(()=>[r(" Show example code ")]),_:1}),e(l(n).Input,{id:"show-example-2",onClick:i,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),t("div",S,[e(l(s).Panel,null,{default:a(()=>[t("div",A,[t("div",T,[e(l(h),{icon:"Calendar",class:"w-4 h-4"})]),e(l(c),{modelValue:u.value,"onUpdate:modelValue":o[1]||(o[1]=d=>u.value=d),options:{autoApply:!1,showWeekNumbers:!0,dropdowns:{minYear:1990,maxYear:null,months:!0,years:!0}},class:"pl-12"},null,8,["modelValue"])])]),_:1}),e(l(s).Panel,{type:"source"},{default:a(()=>[e(l(s).Highlight,null,{default:a(()=>[r(f(`
              <div class="relative w-56 mx-auto">
                <div
                  class="absolute flex items-center justify-center w-10 h-full border rounded-l bg-slate-100 text-slate-500 dark:bg-darkmode-700 dark:border-darkmode-800 dark:text-slate-400"
                >
                  <Lucide icon="Calendar" class="w-4 h-4" />
                </div>
                <Litepicker
                  v-model="date"
                  :options="{
                    autoApply: false,
                    showWeekNumbers: true,
                    dropdowns: {
                      minYear: 1990,
                      maxYear: null,
                      months: true,
                      years: true,
                    },
                  }"
                  class="pl-12"
                />
              </div>
              `))]),_:1})]),_:1})])]),_:1})]),t("div",W,[e(l(s),{class:"intro-y box"},{default:a(({toggle:i})=>[t("div",H,[G,e(l(n),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:a(()=>[e(l(n).Label,{htmlFor:"show-example-3"},{default:a(()=>[r(" Show example code ")]),_:1}),e(l(n).Input,{id:"show-example-3",onClick:i,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),t("div",R,[e(l(s).Panel,null,{default:a(()=>[e(l(c),{modelValue:k.value,"onUpdate:modelValue":o[2]||(o[2]=d=>k.value=d),options:{autoApply:!1,singleMode:!1,numberOfColumns:2,numberOfMonths:2,showWeekNumbers:!0,dropdowns:{minYear:1990,maxYear:null,months:!0,years:!0}},class:"block w-56 mx-auto"},null,8,["modelValue"])]),_:1}),e(l(s).Panel,{type:"source"},{default:a(()=>[e(l(s).Highlight,null,{default:a(()=>[r(f(`
              <Litepicker
                v-model="daterange"
                :options="{
                  autoApply: false,
                  singleMode: false,
                  numberOfColumns: 2,
                  numberOfMonths: 2,
                  showWeekNumbers: true,
                  dropdowns: {
                    minYear: 1990,
                    maxYear: null,
                    months: true,
                    years: true,
                  },
                }"
                class="block w-56 mx-auto"
              />
              `))]),_:1})]),_:1})])]),_:1}),e(l(s),{class:"mt-5 intro-y box"},{default:a(({toggle:i})=>[t("div",U,[O,e(l(n),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:a(()=>[e(l(n).Label,{htmlFor:"show-example-4"},{default:a(()=>[r(" Show example code ")]),_:1}),e(l(n).Input,{id:"show-example-4",onClick:i,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),t("div",$,[e(l(s).Panel,null,{default:a(()=>[t("div",j,[e(l(w),{as:"a",href:"#",variant:"primary",onClick:o[3]||(o[3]=d=>{d.preventDefault(),b(!0)})},{default:a(()=>[r(" Show Modal ")]),_:1})]),e(l(m),{open:x.value,onClose:o[7]||(o[7]=()=>{b(!1)}),initialFocus:v.value},{default:a(()=>[e(l(m).Panel,null,{default:a(()=>[e(l(m).Title,null,{default:a(()=>[z,e(l(w),{variant:"outline-secondary",class:"hidden sm:flex"},{default:a(()=>[e(l(h),{icon:"File",class:"w-4 h-4 mr-2"}),r(" Download Docs ")]),_:1}),e(l(_),{class:"sm:hidden"},{default:a(()=>[e(l(_).Button,{as:"a",class:"block w-5 h-5",href:"#"},{default:a(()=>[e(l(h),{icon:"MoreHorizontal",class:"w-5 h-5 text-slate-500"})]),_:1}),e(l(_).Items,{class:"w-40"},{default:a(()=>[e(l(_).Item,null,{default:a(()=>[e(l(h),{icon:"File",class:"w-4 h-4 mr-2"}),r(" Download Docs ")]),_:1})]),_:1})]),_:1})]),_:1}),e(l(m).Description,{class:"grid grid-cols-12 gap-4 gap-y-3"},{default:a(()=>[t("div",q,[e(l(g),{htmlFor:"modal-datepicker-1"},{default:a(()=>[r(" From ")]),_:1}),e(l(c),{id:"modal-datepicker-1",modelValue:u.value,"onUpdate:modelValue":o[4]||(o[4]=d=>u.value=d),options:{autoApply:!1,showWeekNumbers:!0,dropdowns:{minYear:1990,maxYear:null,months:!0,years:!0}}},null,8,["modelValue"])]),t("div",J,[e(l(g),{htmlFor:"modal-datepicker-2"},{default:a(()=>[r(" To ")]),_:1}),e(l(c),{id:"modal-datepicker-2",modelValue:u.value,"onUpdate:modelValue":o[5]||(o[5]=d=>u.value=d),options:{autoApply:!1,showWeekNumbers:!0,dropdowns:{minYear:1990,maxYear:null,months:!0,years:!0}}},null,8,["modelValue"])])]),_:1}),e(l(m).Footer,{class:"text-right"},{default:a(()=>[e(l(w),{variant:"outline-secondary",type:"button",onClick:o[6]||(o[6]=()=>{b(!1)}),class:"w-20 mr-1"},{default:a(()=>[r(" Cancel ")]),_:1}),e(l(w),{variant:"primary",type:"button",class:"w-20",ref_key:"cancelButtonRef",ref:v},{default:a(()=>[r(" Submit ")]),_:1},512)]),_:1})]),_:1})]),_:1},8,["open","initialFocus"])]),_:1}),e(l(s).Panel,{type:"source"},{default:a(()=>[e(l(s).Highlight,null,{default:a(()=>[r(f(`
              <!-- BEGIN: Show Modal Toggle -->
              <div class="text-center">
                <Button
                  as="a"
                  href="#"
                  variant="primary"
                  @click="(event: MouseEvent) => {
                    event.preventDefault();
                    setDatepickerModalPreview(true);
                  }"
                >
                  Show Modal
                </Button>
              </div>
              <!-- END: Show Modal Toggle -->
              <!-- BEGIN: Modal Content -->
              <Dialog
                :open="datepickerModalPreview"
                @close="
                  () => {
                    setDatepickerModalPreview(false);
                  }
                "
                :initialFocus="cancelButtonRef"
              >
                <Dialog.Panel>
                  <!-- BEGIN: Modal Header -->
                  <Dialog.Title>
                    <h2 class="mr-auto text-base font-medium">
                      Filter by Date
                    </h2>
                    <Button variant="outline-secondary" class="hidden sm:flex">
                      <Lucide icon="File" class="w-4 h-4 mr-2" /> Download Docs
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
                  </Dialog.Title>
                  <!-- END: Modal Header -->
                  <!-- BEGIN: Modal Body -->
                  <Dialog.Description class="grid grid-cols-12 gap-4 gap-y-3">
                    <div class="col-span-12 sm:col-span-6">
                      <FormLabel htmlFor="modal-datepicker-1"> From </FormLabel>
                      <Litepicker
                        id="modal-datepicker-1"
                        v-model="date"
                        :options="{
                          autoApply: false,
                          showWeekNumbers: true,
                          dropdowns: {
                            minYear: 1990,
                            maxYear: null,
                            months: true,
                            years: true,
                          },
                        }"
                      />
                    </div>
                    <div class="col-span-12 sm:col-span-6">
                      <FormLabel htmlFor="modal-datepicker-2"> To </FormLabel>
                      <Litepicker
                        id="modal-datepicker-2"
                        v-model="date"
                        :options="{
                          autoApply: false,
                          showWeekNumbers: true,
                          dropdowns: {
                            minYear: 1990,
                            maxYear: null,
                            months: true,
                            years: true,
                          },
                        }"
                      />
                    </div>
                  </Dialog.Description>
                  <!-- END: Modal Body -->
                  <!-- BEGIN: Modal Footer -->
                  <Dialog.Footer class="text-right">
                    <Button
                      variant="outline-secondary"
                      type="button"
                      @click="
                        () => {
                          setDatepickerModalPreview(false);
                        }
                      "
                      class="w-20 mr-1"
                    >
                      Cancel
                    </Button>
                    <Button
                      variant="primary"
                      type="button"
                      class="w-20"
                      ref="cancelButtonRef"
                    >
                      Submit
                    </Button>
                  </Dialog.Footer>
                  <!-- END: Modal Footer -->
                </Dialog.Panel>
              </Dialog>
              <!-- END: Modal Content -->
              `),1)]),_:1})]),_:1})])]),_:1})])])],64))}});export{ae as default};
