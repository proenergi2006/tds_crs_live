import{P as a}from"./index-9tI8BQBI.js";import{d as E,r as f,f as D,g as o,h as i,w as e,u as t,F as A,p as m,o as I,C as c,i as n,t as l,_ as h,s as _}from"./main-CeS48KJa.js";import{_ as u}from"./Notification.vue_vue_type_script_setup_true_lang-Wd94a-Ji.js";import{_ as d}from"./Button.vue_vue_type_script_setup_true_lang-qNMd5tCb.js";const P=o("div",{class:"flex items-center mt-8 intro-y"},[o("h2",{class:"mr-auto text-lg font-medium"},"Notification")],-1),H={class:"grid grid-cols-12 gap-6 mt-5"},K={class:"col-span-12 intro-y lg:col-span-6"},G={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},R=o("h2",{class:"mr-auto text-base font-medium"},"Basic Notification",-1),F={class:"p-5"},L={class:"text-center"},j=o("div",{class:"font-medium"},"Yay! Updates Published!",-1),M=o("a",{class:"mt-1 font-medium text-primary dark:text-slate-400 sm:mt-0 sm:ml-40",href:""}," Review Changes ",-1),V=o("div",{class:"font-medium"},"Yay! Updates Published!",-1),U=o("a",{class:"mt-1 font-medium text-primary dark:text-slate-400 sm:mt-0 sm:ml-40",href:""}," Review Changes ",-1),Y={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},$=o("h2",{class:"mr-auto text-base font-medium"},"Success Notification",-1),q={class:"p-5"},z={class:"text-center"},J=o("div",{class:"ml-4 mr-4"},[o("div",{class:"font-medium"},"Message Saved!"),o("div",{class:"mt-1 text-slate-500"}," The message will be sent in 5 minutes. ")],-1),O={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},Q=o("h2",{class:"mr-auto text-base font-medium"}," Notification With Actions ",-1),X={class:"p-5"},Z={class:"text-center"},tt=o("div",{class:"ml-4 mr-4"},[o("div",{class:"font-medium"},"Storage Removed!"),o("div",{class:"mt-1 text-slate-500"},[n(" The server will restart in 30 seconds, don't make "),o("br"),n(" changes during the update process! ")]),o("div",{class:"font-medium flex mt-1.5"},[o("a",{class:"text-primary dark:text-slate-400",href:""}," Restart Now "),o("a",{class:"ml-3 text-slate-500",href:""}," Cancel ")])],-1),it={class:"col-span-12 intro-y lg:col-span-6"},ot={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},et=o("h2",{class:"mr-auto text-base font-medium"}," Notification With Avatar ",-1),st={class:"p-5"},at={class:"text-center"},nt={class:"flex-none w-10 h-10 overflow-hidden rounded-full image-fit"},ct=["src"],lt={class:"ml-4 sm:mr-28"},rt={class:"font-medium"},dt=o("div",{class:"mt-1 text-slate-500"},"See you later! 😃😃😃",-1),ft=o("a",{"data-dismiss":"notification",class:"absolute top-0 bottom-0 right-0 flex items-center px-6 font-medium border-l border-slate-200/60 dark:border-darkmode-400 text-primary dark:text-slate-400",href:"#"}," Reply ",-1),mt={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},ut=o("h2",{class:"mr-auto text-base font-medium"}," Notification With Split Buttons ",-1),ht={class:"p-5"},_t={class:"text-center"},vt=o("div",{class:"sm:mr-40"},[o("div",{class:"font-medium"},"Introducing new theme"),o("div",{class:"mt-1 text-slate-500"},"Release version 2.3.3")],-1),xt=o("div",{class:"absolute top-0 bottom-0 right-0 flex flex-col border-l border-slate-200/60 dark:border-darkmode-400"},[o("a",{class:"flex items-center justify-center flex-1 px-6 font-medium border-b text-primary dark:text-slate-400 border-slate-200/60 dark:border-darkmode-400",href:"#"}," View Details "),o("a",{"data-dismiss":"notification",class:"flex items-center justify-center flex-1 px-6 font-medium text-slate-500",href:"#"}," Dismiss ")],-1),pt={class:"flex flex-col items-center p-5 border-b sm:flex-row border-slate-200/60 dark:border-darkmode-400"},Nt=o("h2",{class:"mr-auto text-base font-medium"}," Notification With Buttons Below ",-1),gt={class:"p-5"},wt={class:"text-center"},yt={class:"ml-4 mr-5 sm:mr-20"},bt={class:"font-medium"},kt=o("div",{class:"mt-1 text-slate-500"},"Sent you new documents.",-1),St={class:"mt-2.5"},At=E({__name:"Notification",setup(Bt){const v=f(),b=()=>{var s;(s=v.value)==null||s.showToast()};m("bind[basicNonStickyNotification]",s=>{v.value=s});const x=f(),k=()=>{var s;(s=x.value)==null||s.showToast()};m("bind[basicStickyNotification]",s=>{x.value=s});const p=f(),S=()=>{var s;(s=p.value)==null||s.showToast()};m("bind[successNotification]",s=>{p.value=s});const N=f(),B=()=>{var s;(s=N.value)==null||s.showToast()};m("bind[notificationWithActions]",s=>{N.value=s});const g=f(),T=()=>{var s;(s=g.value)==null||s.showToast()};m("bind[notificationWithAvatar]",s=>{g.value=s});const w=f(),C=()=>{var s;(s=w.value)==null||s.showToast()};m("bind[notificationWithSplitButtons]",s=>{w.value=s});const y=f(),W=()=>{var s;(s=y.value)==null||s.showToast()};return m("bind[notificationWithButtonsBelow]",s=>{y.value=s}),(s,Tt)=>(I(),D(A,null,[P,o("div",H,[o("div",K,[i(t(a),{class:"intro-y box"},{default:e(({toggle:r})=>[o("div",G,[R,i(t(c),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:e(()=>[i(t(c).Label,{htmlFor:"show-example-1"},{default:e(()=>[n(" Show example code ")]),_:1}),i(t(c).Input,{id:"show-example-1",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",F,[i(t(a).Panel,null,{default:e(()=>[o("div",L,[i(t(u),{refKey:"basicNonStickyNotification",options:{duration:3e3},class:"flex flex-col sm:flex-row"},{default:e(()=>[j,M]),_:1}),i(t(u),{refKey:"basicStickyNotification",class:"flex flex-col sm:flex-row"},{default:e(()=>[V,U]),_:1}),i(t(d),{variant:"primary",class:"mr-1",onClick:b},{default:e(()=>[n(" Show Non Sticky Notification ")]),_:1}),i(t(d),{variant:"primary",class:"mt-2 sm:mt-0",onClick:k},{default:e(()=>[n(" Show Sticky Notification ")]),_:1})])]),_:1}),i(t(a).Panel,{type:"source"},{default:e(()=>[i(t(a).Highlight,null,{default:e(()=>[n(l(`
              <div class="text-center">
                <!-- BEGIN: Basic Non Sticky Notification Content -->
                <Notification
                  refKey="basicNonStickyNotification"
                  :options="{
                    duration: 3000,
                  }"
                  class="flex flex-col sm:flex-row"
                >
                  <div class="font-medium">Yay! Updates Published!</div>
                  <a
                    class="mt-1 font-medium text-primary dark:text-slate-400 sm:mt-0 sm:ml-40"
                    href=""
                  >
                    Review Changes
                  </a>
                </Notification>
                <!-- END: Basic Non Sticky Notification Content -->
                <!-- BEGIN: Basic Sticky Notification Content -->
                <Notification
                  refKey="basicStickyNotification"
                  class="flex flex-col sm:flex-row"
                >
                  <div class="font-medium">Yay! Updates Published!</div>
                  <a
                    class="mt-1 font-medium text-primary dark:text-slate-400 sm:mt-0 sm:ml-40"
                    href=""
                  >
                    Review Changes
                  </a>
                </Notification>
                <!-- END: Basic Sticky Notification Content -->
                <!-- BEGIN: Notification Toggle -->
                <Button
                  variant="primary"
                  class="mr-1"
                  @click="basicNonStickyNotificationToggle"
                >
                  Show Non Sticky Notification
                </Button>
                <Button
                  variant="primary"
                  class="mt-2 sm:mt-0"
                  @click="basicStickyNotificationToggle"
                >
                  Show Sticky Notification
                </Button>
                <!-- END: Notification Toggle -->
              </div>
              `))]),_:1}),i(t(a).Highlight,{type:"javascript",class:"mt-5"},{default:e(()=>[n(l(`
                // Basic non sticky notification
                const basicNonStickyNotification = ref<NotificationElement>();
                const basicNonStickyNotificationToggle = () => {
                  // Show notification
                  basicNonStickyNotification.current?.showToast();
                };
              
                // Basic sticky notification
                const basicStickyNotification = ref<NotificationElement>();
                const basicStickyNotificationToggle = () => {
                  // Show notification
                  basicStickyNotification.current?.showToast();
                };
              `),1)]),_:1})]),_:1})])]),_:1}),i(t(a),{class:"mt-5 intro-y box"},{default:e(({toggle:r})=>[o("div",Y,[$,i(t(c),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:e(()=>[i(t(c).Label,{htmlFor:"show-example-2"},{default:e(()=>[n(" Show example code ")]),_:1}),i(t(c).Input,{id:"show-example-2",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",q,[i(t(a).Panel,null,{default:e(()=>[o("div",z,[i(t(u),{refKey:"successNotification",class:"flex"},{default:e(()=>[i(t(h),{icon:"CheckCircle",class:"text-success"}),J]),_:1}),i(t(d),{variant:"primary",onClick:S},{default:e(()=>[n(" Show Notification ")]),_:1})])]),_:1}),i(t(a).Panel,{type:"source"},{default:e(()=>[i(t(a).Highlight,null,{default:e(()=>[n(l(`
                <div class="text-center">
                  <!-- BEGIN: Notification Content -->
                  <Notification refKey="successNotification" class="flex">
                    <Lucide icon="CheckCircle" class="text-success" />
                    <div class="ml-4 mr-4">
                      <div class="font-medium">Message Saved!</div>
                      <div class="mt-1 text-slate-500">
                        The message will be sent in 5 minutes.
                      </div>
                    </div>
                  </Notification>
                  <!-- END: Notification Content -->
                  <!-- BEGIN: Notification Toggle -->
                  <Button variant="primary" @click="successNotificationToggle">
                    Show Notification
                  </Button>
                  <!-- END: Notification Toggle -->
                </div>
              `),1)]),_:1}),i(t(a).Highlight,{type:"javascript",class:"mt-5"},{default:e(()=>[n(l(`
                // Success notification
                const successNotification = ref<NotificationElement>();
                const successNotificationToggle = () => {
                  // Show notification
                  successNotification.current?.showToast();
                };
              `),1)]),_:1})]),_:1})])]),_:1}),i(t(a),{class:"mt-5 intro-y box"},{default:e(({toggle:r})=>[o("div",O,[Q,i(t(c),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:e(()=>[i(t(c).Label,{htmlFor:"show-example-3"},{default:e(()=>[n(" Show example code ")]),_:1}),i(t(c).Input,{id:"show-example-3",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",X,[i(t(a).Panel,null,{default:e(()=>[o("div",Z,[i(t(u),{refKey:"notificationWithActions",class:"flex"},{default:e(()=>[i(t(h),{icon:"HardDrive"}),tt]),_:1}),i(t(d),{variant:"primary",onClick:B},{default:e(()=>[n(" Show Notification ")]),_:1})])]),_:1}),i(t(a).Panel,{type:"source"},{default:e(()=>[i(t(a).Highlight,null,{default:e(()=>[n(l(`
              <div class="text-center">
                <!-- BEGIN: Notification Content -->
                <Notification refKey="notificationWithActions" class="flex">
                  <Lucide icon="HardDrive" />
                  <div class="ml-4 mr-4">
                    <div class="font-medium">Storage Removed!</div>
                    <div class="mt-1 text-slate-500">
                      The server will restart in 30 seconds, don't make
                      <br />
                      changes during the update process!
                    </div>
                    <div class="font-medium flex mt-1.5">
                      <a class="text-primary dark:text-slate-400" href="">
                        Restart Now
                      </a>
                      <a class="ml-3 text-slate-500" href=""> Cancel </a>
                    </div>
                  </div>
                </Notification>
                <!-- END: Notification Content -->
                <!-- BEGIN: Notification Toggle -->
                <Button
                  variant="primary"
                  @click="notificationWithActionsToggle"
                >
                  Show Notification
                </Button>
                <!-- END: Notification Toggle -->
              </div>
              `))]),_:1}),i(t(a).Highlight,{type:"javascript",class:"mt-5"},{default:e(()=>[n(l(`
                // Notification with actions
                const notificationWithActions = ref<NotificationElement>();
                const notificationWithActionsToggle = () => {
                  // Show notification
                  notificationWithActions.current?.showToast();
                };  
              `),1)]),_:1})]),_:1})])]),_:1})]),o("div",it,[i(t(a),{class:"intro-y box"},{default:e(({toggle:r})=>[o("div",ot,[et,i(t(c),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:e(()=>[i(t(c).Label,{htmlFor:"show-example-4"},{default:e(()=>[n(" Show example code ")]),_:1}),i(t(c).Input,{id:"show-example-4",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",st,[i(t(a).Panel,null,{default:e(()=>[o("div",at,[i(t(u),{refKey:"notificationWithAvatar",options:{close:!1},class:"flex"},{default:e(()=>[o("div",nt,[o("img",{alt:"Midone Tailwind HTML Admin Template",src:t(_)[0].photos[0]},null,8,ct)]),o("div",lt,[o("div",rt,l(t(_)[0].users[0].name),1),dt]),ft]),_:1}),i(t(d),{variant:"primary",onClick:T},{default:e(()=>[n(" Show Notification ")]),_:1})])]),_:1}),i(t(a).Panel,{type:"source"},{default:e(()=>[i(t(a).Highlight,null,{default:e(()=>[n(l(`
              <div class="text-center">
                <!-- BEGIN: Notification Content -->
                <Notification
                  refKey="notificationWithAvatar"
                  :options="{
                    close: false,
                  }"
                  class="flex"
                >
                  <div
                    class="flex-none w-10 h-10 overflow-hidden rounded-full image-fit"
                  >
                    <img
                      alt="Midone Tailwind HTML Admin Template"
                      :src="fakerData[0].photos[0]"
                    />
                  </div>
                  <div class="ml-4 sm:mr-28">
                    <div class="font-medium">
                      {{ fakerData[0].users[0].name }}
                    </div>
                    <div class="mt-1 text-slate-500">See you later! 😃😃😃</div>
                  </div>
                  <a
                    data-dismiss="notification"
                    class="absolute top-0 bottom-0 right-0 flex items-center px-6 font-medium border-l border-slate-200/60 dark:border-darkmode-400 text-primary dark:text-slate-400"
                    href="#"
                  >
                    Reply
                  </a>
                </Notification>
                <!-- END: Notification Content -->
                <!-- BEGIN: Notification Toggle -->
                <Button variant="primary" @click="notificationWithAvatarToggle">
                  Show Notification
                </Button>
                <!-- END: Notification Toggle -->
              </div>
              `),1)]),_:1}),i(t(a).Highlight,{type:"javascript",class:"mt-5"},{default:e(()=>[n(l(`
                // Notification with avatar
                const notificationWithAvatar = ref<NotificationElement>();
                const notificationWithAvatarToggle = () => {
                  // Show notification
                  notificationWithAvatar.current?.showToast();
                };
              `),1)]),_:1})]),_:1})])]),_:1}),i(t(a),{class:"mt-5 intro-y box"},{default:e(({toggle:r})=>[o("div",mt,[ut,i(t(c),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:e(()=>[i(t(c).Label,{htmlFor:"show-example-5"},{default:e(()=>[n(" Show example code ")]),_:1}),i(t(c).Input,{id:"show-example-5",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",ht,[i(t(a).Panel,null,{default:e(()=>[o("div",_t,[i(t(u),{refKey:"notificationWithSplitButtons",options:{close:!1},class:"flex"},{default:e(()=>[vt,xt]),_:1}),i(t(d),{variant:"primary",onClick:C},{default:e(()=>[n(" Show Notification ")]),_:1})])]),_:1}),i(t(a).Panel,{type:"source"},{default:e(()=>[i(t(a).Highlight,null,{default:e(()=>[n(l(`
              <div class="text-center">
                <!-- BEGIN: Notification Content -->
                <Notification
                  refKey="notificationWithSplitButtons"
                  :options="{
                    close: false,
                  }"
                  class="flex"
                >
                  <div class="sm:mr-40">
                    <div class="font-medium">Introducing new theme</div>
                    <div class="mt-1 text-slate-500">Release version 2.3.3</div>
                  </div>
                  <div
                    class="absolute top-0 bottom-0 right-0 flex flex-col border-l border-slate-200/60 dark:border-darkmode-400"
                  >
                    <a
                      class="flex items-center justify-center flex-1 px-6 font-medium border-b text-primary dark:text-slate-400 border-slate-200/60 dark:border-darkmode-400"
                      href="#"
                    >
                      View Details
                    </a>
                    <a
                      data-dismiss="notification"
                      class="flex items-center justify-center flex-1 px-6 font-medium text-slate-500"
                      href="#"
                    >
                      Dismiss
                    </a>
                  </div>
                </Notification>
                <!-- END: Notification Content -->
                <!-- BEGIN: Notification Toggle -->
                <Button
                  variant="primary"
                  @click="notificationWithSplitButtonsToggle"
                >
                  Show Notification
                </Button>
                <!-- END: Notification Toggle -->
              </div>
              `))]),_:1}),i(t(a).Highlight,{type:"javascript",class:"mt-5"},{default:e(()=>[n(l(`
                // Notification with split buttons
                const notificationWithSplitButtons = ref<NotificationElement>();
                const notificationWithSplitButtonsToggle = () => {
                  // Show notification
                  notificationWithSplitButtons.current?.showToast();
                };
              `),1)]),_:1})]),_:1})])]),_:1}),i(t(a),{class:"mt-5 intro-y box"},{default:e(({toggle:r})=>[o("div",pt,[Nt,i(t(c),{class:"w-full mt-3 sm:w-auto sm:ml-auto sm:mt-0"},{default:e(()=>[i(t(c).Label,{htmlFor:"show-example-6"},{default:e(()=>[n(" Show example code ")]),_:1}),i(t(c).Input,{id:"show-example-6",onClick:r,class:"ml-3 mr-0",type:"checkbox"},null,8,["onClick"])]),_:2},1024)]),o("div",gt,[i(t(a).Panel,null,{default:e(()=>[o("div",wt,[i(t(u),{refKey:"notificationWithButtonsBelow",options:{close:!1},class:"flex"},{default:e(()=>[i(t(h),{icon:"FileText"}),o("div",yt,[o("div",bt,l(t(_)[0].users[0].name),1),kt,o("div",St,[i(t(d),{variant:"primary",as:"a",class:"px-2 py-1 mr-2",href:""},{default:e(()=>[n(" Preview ")]),_:1}),i(t(d),{variant:"outline-secondary",as:"a",class:"px-2 py-1",href:""},{default:e(()=>[n(" Download ")]),_:1})])])]),_:1}),i(t(d),{variant:"primary",onClick:W},{default:e(()=>[n(" Show Notification ")]),_:1})])]),_:1}),i(t(a).Panel,{type:"source"},{default:e(()=>[i(t(a).Highlight,null,{default:e(()=>[n(l(`
              <div class="text-center">
                <!-- BEGIN: Notification Content -->
                <Notification
                  refKey="notificationWithButtonsBelow"
                  :options="{
                    close: false,
                  }"
                  class="flex"
                >
                  <Lucide icon="FileText" />
                  <div class="ml-4 mr-5 sm:mr-20">
                    <div class="font-medium">
                      {{ fakerData[0].users[0].name }}
                    </div>
                    <div class="mt-1 text-slate-500">
                      Sent you new documents.
                    </div>
                    <div class="mt-2.5">
                      <Button
                        variant="primary"
                        as="a"
                        class="px-2 py-1 mr-2"
                        href=""
                      >
                        Preview
                      </Button>
                      <Button
                        variant="outline-secondary"
                        as="a"
                        class="px-2 py-1"
                        href=""
                      >
                        Download
                      </Button>
                    </div>
                  </div>
                </Notification>
                <!-- END: Notification Content -->
                <!-- BEGIN: Notification Toggle -->
                <Button
                  variant="primary"
                  @click="notificationWithButtonsBelowToggle"
                >
                  Show Notification
                </Button>
                <!-- END: Notification Toggle -->
              </div>
              `),1)]),_:1}),i(t(a).Highlight,{type:"javascript",class:"mt-5"},{default:e(()=>[n(l(`
                // Notification with buttons below
                const notificationWithButtonsBelow = ref<NotificationElement>();
                const notificationWithButtonsBelowToggle = () => {
                  // Show notification
                  notificationWithButtonsBelow.current?.showToast();
                };
              `),1)]),_:1})]),_:1})])]),_:1})])])],64))}});export{At as default};
