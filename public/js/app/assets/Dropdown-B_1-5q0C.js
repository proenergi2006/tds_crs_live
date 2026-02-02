import{P as o}from"./index-9tI8BQBI.js";import{d as _,r as f,f as M,g as s,h as t,w as l,u as e,F as I,o as h,C as m,i as a,T as n,t as c,_ as d,aU as i,m as p}from"./main-CeS48KJa.js";import{_ as u}from"./Button.vue_vue_type_script_setup_true_lang-qNMd5tCb.js";const x=s("div",{class:"flex items-center mt-8 intro-y"},[s("h2",{class:"mr-auto text-lg font-medium"},"Dropdown")],-1),b={class:"grid grid-cols-12 gap-6 mt-5"},D={class:"col-span-12 lg:col-span-6"},v={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},y=s("h2",{class:"mr-auto text-base font-medium"},"Basic Dropdown",-1),B={class:"p-5"},k={class:"flex justify-center"},g={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},S=s("h2",{class:"mr-auto text-base font-medium"}," Header & Footer Dropdown ",-1),P={class:"p-5"},N={class:"flex justify-center"},C=s("div",{class:"px-1 ml-auto text-xs text-white rounded-full bg-danger"}," 10 ",-1),L={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},F=s("h2",{class:"mr-auto text-base font-medium"},"Icon Dropdown",-1),E={class:"p-5"},T={class:"flex justify-center"},H={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},V=s("h2",{class:"mr-auto text-base font-medium"}," Dropdown with close button ",-1),J={class:"p-5"},R={class:"text-center"},j={class:"p-2"},A=s("div",{class:"text-xs text-left"},"From",-1),$={class:"mt-3"},O=s("div",{class:"text-xs text-left"},"To",-1),U={class:"flex items-center mt-3"},q={class:"col-span-12 lg:col-span-6"},z={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},G=s("h2",{class:"mr-auto text-base font-medium"},"Scrolled Dropdown",-1),K={class:"p-5"},Q={class:"flex justify-center"},W={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},X=s("h2",{class:"mr-auto text-base font-medium"},"Header & Icon Dropdown",-1),Y={class:"p-5"},Z={class:"flex justify-center"},ee={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},te=s("h2",{class:"mr-auto text-base font-medium"},"Dropdown Placement",-1),le={class:"p-5"},ne={class:"text-center"},de=_({__name:"Dropdown",setup(ae){return f(!1),(se,oe)=>(h(),M(I,null,[x,s("div",b,[s("div",D,[t(e(o),{class:"intro-y box"},{default:l(({toggle:r})=>[s("div",v,[y,t(e(m),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:l(()=>[t(e(m).Label,{htmlFor:"show-example-1"},{default:l(()=>[a(" Show example code ")]),_:1}),t(e(m).Input,{id:"show-example-1",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),s("div",B,[t(e(o).Panel,null,{default:l(()=>[s("div",k,[t(e(n),null,{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary"},{default:l(()=>[a(" Show Dropdown ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1})])]),_:1}),t(e(o).Panel,{type:"source"},{default:l(()=>[t(e(o).Highlight,null,{default:l(()=>[a(c(`
              <Menu>
                <Menu.Button :as="Button" variant="primary">
                  Show Dropdown
                </Menu.Button>
                <Menu.Items class="w-40">
                  <Menu.Item>New Dropdown</Menu.Item>
                  <Menu.Item>Delete Dropdown</Menu.Item>
                </Menu.Items>
              </Menu>
              `),1)]),_:1})]),_:1})])]),_:1}),t(e(o),{class:"mt-5 intro-y box"},{default:l(({toggle:r})=>[s("div",g,[S,t(e(m),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:l(()=>[t(e(m).Label,{htmlFor:"show-example-2"},{default:l(()=>[a(" Show example code ")]),_:1}),t(e(m).Input,{id:"show-example-2",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),s("div",P,[t(e(o).Panel,null,{default:l(()=>[s("div",N,[t(e(n),null,{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary"},{default:l(()=>[a(" Show Dropdown ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-56"},{default:l(()=>[t(e(n).Header,null,{default:l(()=>[a("Export Options")]),_:1}),t(e(n).Divider),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Activity",class:"w-4 h-4 mr-2"}),a(" English ")]),_:1}),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Box",class:"w-4 h-4 mr-2"}),a(" Indonesia "),C]),_:1}),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Layout",class:"w-4 h-4 mr-2"}),a(" English ")]),_:1}),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Sidebar",class:"w-4 h-4 mr-2"}),a(" Indonesia ")]),_:1}),t(e(n).Divider),t(e(n).Footer,null,{default:l(()=>[t(e(u),{type:"button",variant:"primary",class:"px-2 py-1"},{default:l(()=>[a(" Settings ")]),_:1}),t(e(u),{type:"button",variant:"secondary",class:"px-2 py-1 ml-auto"},{default:l(()=>[a(" View Profile ")]),_:1})]),_:1})]),_:1})]),_:1})])]),_:1}),t(e(o).Panel,{type:"source"},{default:l(()=>[t(e(o).Highlight,null,{default:l(()=>[a(c(`
              <Menu>
                <Menu.Button :as="Button" variant="primary">
                  Show Dropdown
                </Menu.Button>
                <Menu.Items class="w-56">
                  <Menu.Header>Export Options</Menu.Header>
                  <Menu.Divider />
                  <Menu.Item>
                    <Lucide icon="Activity" class="w-4 h-4 mr-2" />
                    English
                  </Menu.Item>
                  <Menu.Item>
                    <Lucide icon="Box" class="w-4 h-4 mr-2" />
                    Indonesia
                    <div
                      class="px-1 ml-auto text-xs text-white rounded-full bg-danger"
                    >
                      10
                    </div>
                  </Menu.Item>
                  <Menu.Item>
                    <Lucide icon="Layout" class="w-4 h-4 mr-2" />
                    English
                  </Menu.Item>
                  <Menu.Item>
                    <Lucide icon="Sidebar" class="w-4 h-4 mr-2" />
                    Indonesia
                  </Menu.Item>
                  <Menu.Divider />
                  <Menu.Footer>
                    <Button type="button" variant="primary" class="px-2 py-1">
                      Settings
                    </Button>
                    <Button
                      type="button"
                      variant="secondary"
                      class="px-2 py-1 ml-auto"
                    >
                      View Profile
                    </Button>
                  </Menu.Footer>
                </Menu.Items>
              </Menu>
              `),1)]),_:1})]),_:1})])]),_:1}),t(e(o),{class:"mt-5 intro-y box"},{default:l(({toggle:r})=>[s("div",L,[F,t(e(m),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:l(()=>[t(e(m).Label,{htmlFor:"show-example-3"},{default:l(()=>[a(" Show example code ")]),_:1}),t(e(m).Input,{id:"show-example-3",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),s("div",E,[t(e(o).Panel,null,{default:l(()=>[s("div",T,[t(e(n),null,{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary"},{default:l(()=>[a(" Show Dropdown ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-48"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Edit2",class:"w-4 h-4 mr-2"}),a(" New Dropdown ")]),_:1}),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Trash",class:"w-4 h-4 mr-2"}),a(" Delete Dropdown ")]),_:1})]),_:1})]),_:1})])]),_:1}),t(e(o).Panel,{type:"source"},{default:l(()=>[t(e(o).Highlight,null,{default:l(()=>[a(c(`
              <Menu>
                <Menu.Button :as="Button" variant="primary">
                  Show Dropdown
                </Menu.Button>
                <Menu.Items class="w-48">
                  <Menu.Item>
                    <Lucide icon="Edit2" class="w-4 h-4 mr-2" /> New Dropdown
                  </Menu.Item>
                  <Menu.Item>
                    <Lucide icon="Trash" class="w-4 h-4 mr-2" /> Delete Dropdown
                  </Menu.Item>
                </Menu.Items>
              </Menu>
              `),1)]),_:1})]),_:1})])]),_:1}),t(e(o),{class:"mt-5 intro-y box"},{default:l(({toggle:r})=>[s("div",H,[V,t(e(m),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:l(()=>[t(e(m).Label,{htmlFor:"show-example-4"},{default:l(()=>[a(" Show example code ")]),_:1}),t(e(m).Input,{id:"show-example-4",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),s("div",J,[t(e(o).Panel,null,{default:l(()=>[s("div",R,[t(e(i),{class:"inline-block"},{default:l(({close:w})=>[t(e(i).Button,{as:e(u),variant:"primary"},{default:l(()=>[a(" Filter Dropdown "),t(e(d),{icon:"ChevronDown",class:"w-4 h-4 ml-2"})]),_:1},8,["as"]),t(e(i).Panel,{placement:"bottom-start"},{default:l(()=>[s("div",j,[s("div",null,[A,t(e(p),{type:"text",class:"flex-1 mt-2",placeholder:"example@gmail.com"})]),s("div",$,[O,t(e(p),{type:"text",class:"flex-1 mt-2",placeholder:"example@gmail.com"})]),s("div",U,[t(e(u),{variant:"secondary",onClick:()=>{w()},class:"w-32 ml-auto"},{default:l(()=>[a(" Close ")]),_:2},1032,["onClick"]),t(e(u),{variant:"primary",class:"w-32 ml-2"},{default:l(()=>[a(" Search ")]),_:1})])])]),_:2},1024)]),_:1})])]),_:1}),t(e(o).Panel,{type:"source"},{default:l(()=>[t(e(o).Highlight,null,{default:l(()=>[a(c(`
              <div class="text-center">
                <Popover class="inline-block" v-slot="{ close }">
                  <Popover.Button :as="Button" variant="primary">
                    Filter Dropdown
                    <Lucide icon="ChevronDown" class="w-4 h-4 ml-2" />
                  </Popover.Button>
                  <Popover.Panel placement="bottom-start">
                    <div class="p-2">
                      <div>
                        <div class="text-xs text-left">From</div>
                        <FormInput
                          type="text"
                          class="flex-1 mt-2"
                          placeholder="example@gmail.com"
                        />
                      </div>
                      <div class="mt-3">
                        <div class="text-xs text-left">To</div>
                        <FormInput
                          type="text"
                          class="flex-1 mt-2"
                          placeholder="example@gmail.com"
                        />
                      </div>
                      <div class="flex items-center mt-3">
                        <Button
                          variant="secondary"
                          @click="
                            () => {
                              close();
                            }
                          "
                          class="w-32 ml-auto"
                        >
                          Close
                        </Button>
                        <Button variant="primary" class="w-32 ml-2">
                          Search
                        </Button>
                      </div>
                    </div>
                  </Popover.Panel>
                </Popover>
              </div>
              `),1)]),_:1})]),_:1})])]),_:1})]),s("div",q,[t(e(o),{class:"intro-y box"},{default:l(({toggle:r})=>[s("div",z,[G,t(e(m),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:l(()=>[t(e(m).Label,{htmlFor:"show-example-5"},{default:l(()=>[a(" Show example code ")]),_:1}),t(e(m).Input,{id:"show-example-5",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),s("div",K,[t(e(o).Panel,null,{default:l(()=>[s("div",Q,[t(e(n),null,{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary"},{default:l(()=>[a(" Show Dropdown ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40 h-32 overflow-y-auto"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("January")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("February")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("March")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("June")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("July")]),_:1})]),_:1})]),_:1})])]),_:1}),t(e(o).Panel,{type:"source"},{default:l(()=>[t(e(o).Highlight,null,{default:l(()=>[a(c(`
              <Menu>
                <Menu.Button :as="Button" variant="primary">
                  Show Dropdown
                </Menu.Button>
                <Menu.Items class="w-40 h-32 overflow-y-auto">
                  <Menu.Item>January</Menu.Item>
                  <Menu.Item>February</Menu.Item>
                  <Menu.Item>March</Menu.Item>
                  <Menu.Item>June</Menu.Item>
                  <Menu.Item>July</Menu.Item>
                </Menu.Items>
              </Menu>
              `),1)]),_:1})]),_:1})])]),_:1}),t(e(o),{class:"mt-5 intro-y box"},{default:l(({toggle:r})=>[s("div",W,[X,t(e(m),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:l(()=>[t(e(m).Label,{htmlFor:"show-example-6"},{default:l(()=>[a(" Show example code ")]),_:1}),t(e(m).Input,{id:"show-example-6",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),s("div",Y,[t(e(o).Panel,null,{default:l(()=>[s("div",Z,[t(e(n),null,{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary"},{default:l(()=>[a(" Show Dropdown ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40"},{default:l(()=>[t(e(n).Header,null,{default:l(()=>[a("Export Tools")]),_:1}),t(e(n).Divider),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Printer",class:"w-4 h-4 mr-2"}),a(" Print ")]),_:1}),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"ExternalLink",class:"w-4 h-4 mr-2"}),a(" Excel ")]),_:1}),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"FileText",class:"w-4 h-4 mr-2"}),a(" CSV ")]),_:1}),t(e(n).Item,null,{default:l(()=>[t(e(d),{icon:"Archive",class:"w-4 h-4 mr-2"}),a(" PDF ")]),_:1})]),_:1})]),_:1})])]),_:1}),t(e(o).Panel,{type:"source"},{default:l(()=>[t(e(o).Highlight,null,{default:l(()=>[a(c(`
              <Menu>
                <Menu.Button :as="Button" variant="primary">
                  Show Dropdown
                </Menu.Button>
                <Menu.Items class="w-40">
                  <Menu.Header>Export Tools</Menu.Header>
                  <Menu.Divider />
                  <Menu.Item>
                    <Lucide icon="Printer" class="w-4 h-4 mr-2" />
                    Print
                  </Menu.Item>
                  <Menu.Item>
                    <Lucide icon="ExternalLink" class="w-4 h-4 mr-2" />
                    Excel
                  </Menu.Item>
                  <Menu.Item>
                    <Lucide icon="FileText" class="w-4 h-4 mr-2" />
                    CSV
                  </Menu.Item>
                  <Menu.Item>
                    <Lucide icon="Archive" class="w-4 h-4 mr-2" />
                    PDF
                  </Menu.Item>
                </Menu.Items>
              </Menu>
              `),1)]),_:1})]),_:1})])]),_:1}),t(e(o),{class:"mt-5 intro-y box"},{default:l(({toggle:r})=>[s("div",ee,[te,t(e(m),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:l(()=>[t(e(m).Label,{htmlFor:"show-example-7"},{default:l(()=>[a(" Show example code ")]),_:1}),t(e(m).Input,{id:"show-example-7",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),s("div",le,[t(e(o).Panel,null,{default:l(()=>[s("div",ne,[t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Top Start ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"top-start"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Top ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"top"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Top End ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"top-end"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Right Start ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"right-start"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Right ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"right"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Right End ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"right-end"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Bottom End ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"bottom-end"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Bottom ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"bottom"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Bottom Start ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"bottom-start"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Left Start ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"left-start"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Left ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"left"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1}),t(e(n),{class:"inline-block mb-2 mr-1"},{default:l(()=>[t(e(n).Button,{as:e(u),variant:"primary",class:"w-32"},{default:l(()=>[a(" Left End ")]),_:1},8,["as"]),t(e(n).Items,{class:"w-40",placement:"left-end"},{default:l(()=>[t(e(n).Item,null,{default:l(()=>[a("New Dropdown")]),_:1}),t(e(n).Item,null,{default:l(()=>[a("Delete Dropdown")]),_:1})]),_:1})]),_:1})])]),_:1}),t(e(o).Panel,{type:"source"},{default:l(()=>[t(e(o).Highlight,null,{default:l(()=>[a(c(`
              <div class="text-center">
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Top Start
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="top-start">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Top
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="top">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Top End
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="top-end">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Right Start
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="right-start">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Right
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="right">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Right End
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="right-end">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Bottom End
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="bottom-end">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Bottom
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="bottom">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Bottom Start
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="bottom-start">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Left Start
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="left-start">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Left
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="left">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
                <Menu class="inline-block mb-2 mr-1">
                  <Menu.Button :as="Button" variant="primary" class="w-32">
                    Left End
                  </Menu.Button>
                  <Menu.Items class="w-40" placement="left-end">
                    <Menu.Item>New Dropdown</Menu.Item>
                    <Menu.Item>Delete Dropdown</Menu.Item>
                  </Menu.Items>
                </Menu>
              </div>
              `),1)]),_:1})]),_:1})])]),_:1})])])],64))}});export{de as default};
