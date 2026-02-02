<script setup lang="ts">
import _ from "lodash";
import { ref, provide } from "vue";
import fakerData from "@/utils/faker";
import Button from "@/components/Base/Button";
import Pagination from "@/components/Base/Pagination";
import { FormInput, FormSelect } from "@/components/Base/Form";
import TinySlider, {
  type TinySliderElement,
} from "@/components/Base/TinySlider";
import Lucide from "@/components/Base/Lucide";
import Tippy from "@/components/Base/Tippy";
import Litepicker from "@/components/Base/Litepicker";
import ReportDonutChart from "@/components/ReportDonutChart";
import ReportLineChart from "@/components/ReportLineChart";
import ReportPieChart from "@/components/ReportPieChart";
import ReportDonutChart1 from "@/components/ReportDonutChart1";
import SimpleLineChart1 from "@/components/SimpleLineChart1";
import LeafletMap from "@/components/LeafletMap";
import { Menu } from "@/components/Base/Headless";
import Table from "@/components/Base/Table";

const salesReportFilter = ref<string>("");
const importantNotesRef = ref<TinySliderElement>();

provide("bind[importantNotesRef]", (el: TinySliderElement) => {
  importantNotesRef.value = el;
});

const prevImportantNotes = () => {
  importantNotesRef.value?.tns.goTo("prev");
};
const nextImportantNotes = () => {
  importantNotesRef.value?.tns.goTo("next");
};
</script>

<template>
  <div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 2xl:col-span-9">
      <div class="grid grid-cols-12 gap-6">
        <!-- BEGIN: General Report -->
        <div class="col-span-12 mt-8">
          <div class="flex items-center h-10 intro-y">
            <h2 class="mr-5 text-lg font-medium truncate">Dashboard Utama</h2>
           
          </div>
          <div class="grid grid-cols-12 gap-6 mt-5">
            <div class="col-span-12 sm:col-span-6 xl:col-span-3 intro-y">
              <div
                :class="[
                  'relative zoom-in',
                  'before:box before:absolute before:inset-x-3 before:mt-3 before:h-full before:bg-slate-50 before:content-[\'\']',
                ]"
              >
               
              </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3 intro-y">
              <div
                :class="[
                  'relative zoom-in',
                  'before:box before:absolute before:inset-x-3 before:mt-3 before:h-full before:bg-slate-50 before:content-[\'\']',
                ]"
              >
               
              </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3 intro-y">
              <div
                :class="[
                  'relative zoom-in',
                  'before:box before:absolute before:inset-x-3 before:mt-3 before:h-full before:bg-slate-50 before:content-[\'\']',
                ]"
              >
                
                  
              </div>
            </div>
            <div class="col-span-12 sm:col-span-6 xl:col-span-3 intro-y">
              <div
                :class="[
                  'relative zoom-in',
                  'before:box before:absolute before:inset-x-3 before:mt-3 before:h-full before:bg-slate-50 before:content-[\'\']',
                ]"
              >
               
                
                 
              </div>
            </div>
          </div>
        </div>
        <!-- END: General Report -->
        <!-- BEGIN: Sales Report -->
      </div>
    </div>
  </div>
</template>
