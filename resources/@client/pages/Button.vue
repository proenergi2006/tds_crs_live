<script setup lang="ts">
import Lucide from "@/components/Base/Lucide";
import Button from "@/components/Base/Button";
import LoadingIcon from "@/components/Base/LoadingIcon";
import Preview from "@/components/Base/Preview";
import { FormSwitch } from "@/components/Base/Form";
</script>

<template>
  <div class="flex items-center mt-8 intro-y">
    <h2 class="mr-auto font-medium text-lg">Buttons</h2>
  </div>
  <div class="gap-6 grid grid-cols-12 mt-5 intro-y">
    <div class="col-span-12 lg:col-span-6">
      <Preview class="intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Basic Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-1">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-1" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <Button variant="primary" class="mr-1 mb-2 w-24"> Primary </Button>
            <Button variant="secondary" class="mr-1 mb-2 w-24">
              Secondary
            </Button>
            <Button variant="success" class="mr-1 mb-2 w-24"> Success </Button>
            <Button variant="warning" class="mr-1 mb-2 w-24"> Warning </Button>
            <Button variant="info" class="mr-1 mb-2 w-24"> Pending </Button>
            <Button variant="danger" class="mr-1 mb-2 w-24"> Danger </Button>
            <Button variant="dark" class="mr-1 mb-2 w-24"> Dark </Button>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <Button variant="primary" class="mr-1 mb-2 w-24">
                Primary
              </Button>
              <Button variant="secondary" class="mr-1 mb-2 w-24">
                Secondary
              </Button>
              <Button variant="success" class="mr-1 mb-2 w-24">
                Success
              </Button>
              <Button variant="warning" class="mr-1 mb-2 w-24">
                Warning
              </Button>
              <Button variant="info" class="mr-1 mb-2 w-24">
                Pending
              </Button>
              <Button variant="danger" class="mr-1 mb-2 w-24"> Danger </Button>
              <Button variant="dark" class="mr-1 mb-2 w-24"> Dark </Button>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Button Sizes</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-2">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-2" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <div>
              <Button variant="primary" size="sm" class="mr-1 mb-2 w-24">
                Small
              </Button>
              <Button variant="primary" class="mr-1 mb-2 w-24"> Medium </Button>
              <Button variant="primary" size="lg" class="mr-1 mb-2 w-24">
                Large
              </Button>
            </div>
            <div class="mt-5">
              <Button variant="secondary" size="sm" class="mr-1 mb-2 w-24">
                Small
              </Button>
              <Button variant="secondary" class="mr-1 mb-2 w-24">
                Medium
              </Button>
              <Button variant="secondary" size="lg" class="mr-1 mb-2 w-24">
                Large
              </Button>
            </div>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <div>
                <Button variant="primary" size="sm" class="mr-1 mb-2 w-24">
                  Small
                </Button>
                <Button variant="primary" class="mr-1 mb-2 w-24">
                  Medium
                </Button>
                <Button variant="primary" size="lg" class="mr-1 mb-2 w-24">
                  Large
                </Button>
              </div>
              <div class="mt-5">
                <Button variant="secondary" size="sm" class="mr-1 mb-2 w-24">
                  Small
                </Button>
                <Button variant="secondary" class="mr-1 mb-2 w-24">
                  Medium
                </Button>
                <Button variant="secondary" size="lg" class="mr-1 mb-2 w-24">
                  Large
                </Button>
              </div>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Working with Links</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-3">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-3" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <Button as="a" variant="primary" href="" class="inline-block mr-1 mb-2 w-24">
              Link
            </Button>
            <Button as="a" variant="secondary" href="" class="inline-block mr-1 mb-2 w-24">
              Button
            </Button>
            <Button as="a" variant="success" href="" class="inline-block mr-1 mb-2 w-24">
              Input
            </Button>
            <Button as="a" variant="warning" href="" class="inline-block mr-1 mb-2 w-24">
              Submit
            </Button>
            <Button as="a" variant="info" href="" class="inline-block mr-1 mb-2 w-24">
              Pending
            </Button>
            <Button as="a" variant="danger" href="" class="inline-block mr-1 mb-2 w-24">
              Reset
            </Button>
            <Button as="a" variant="dark" href="" class="inline-block mr-1 mb-2 w-24">
              Metal
            </Button>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <Button as="a" variant="primary" href="" class="inline-block mr-1 mb-2 w-24">
                Link
              </Button>
              <Button as="a" variant="secondary" href="" class="inline-block mr-1 mb-2 w-24">
                Button
              </Button>
              <Button as="a" variant="success" href="" class="inline-block mr-1 mb-2 w-24">
                Input
              </Button>
              <Button as="a" variant="warning" href="" class="inline-block mr-1 mb-2 w-24">
                Submit
              </Button>
              <Button as="a" variant="info" href="" class="inline-block mr-1 mb-2 w-24">
                Pending
              </Button>
              <Button as="a" variant="danger" href="" class="inline-block mr-1 mb-2 w-24">
                Reset
              </Button>
              <Button as="a" variant="dark" href="" class="inline-block mr-1 mb-2 w-24">
                Metal
              </Button>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Elevated Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-4">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-4" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <div>
              <Button variant="primary" elevated class="mr-1 mb-2 w-24">
                Primary
              </Button>
              <Button variant="secondary" elevated class="mr-1 mb-2 w-24">
                Secondary
              </Button>
              <Button variant="success" elevated class="mr-1 mb-2 w-24">
                Success
              </Button>
              <Button variant="warning" elevated class="mr-1 mb-2 w-24">
                Warning
              </Button>
              <Button variant="info" elevated class="mr-1 mb-2 w-24">
                Pending
              </Button>
              <Button variant="danger" elevated class="mr-1 mb-2 w-24">
                Danger
              </Button>
              <Button variant="dark" elevated class="mr-1 mb-2 w-24">
                Dark
              </Button>
            </div>
            <div class="mt-5">
              <Button variant="primary" elevated rounded class="mr-1 mb-2 w-24">
                Primary
              </Button>
              <Button variant="secondary" elevated rounded class="mr-1 mb-2 w-24">
                Secondary
              </Button>
              <Button variant="success" elevated rounded class="mr-1 mb-2 w-24">
                Success
              </Button>
              <Button variant="warning" elevated rounded class="mr-1 mb-2 w-24">
                Warning
              </Button>
              <Button variant="info" elevated rounded class="mr-1 mb-2 w-24">
                Pending
              </Button>
              <Button variant="danger" elevated rounded class="mr-1 mb-2 w-24">
                Danger
              </Button>
              <Button variant="dark" elevated rounded class="mr-1 mb-2 w-24">
                Dark
              </Button>
            </div>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <div>
                <Button variant="primary" elevated class="mr-1 mb-2 w-24">
                  Primary
                </Button>
                <Button variant="secondary" elevated class="mr-1 mb-2 w-24">
                  Secondary
                </Button>
                <Button variant="success" elevated class="mr-1 mb-2 w-24">
                  Success
                </Button>
                <Button variant="warning" elevated class="mr-1 mb-2 w-24">
                  Warning
                </Button>
                <Button variant="info" elevated class="mr-1 mb-2 w-24">
                  Pending
                </Button>
                <Button variant="danger" elevated class="mr-1 mb-2 w-24">
                  Danger
                </Button>
                <Button variant="dark" elevated class="mr-1 mb-2 w-24">
                  Dark
                </Button>
              </div>
              <div class="mt-5">
                <Button variant="primary" elevated rounded class="mr-1 mb-2 w-24">
                  Primary
                </Button>
                <Button variant="secondary" elevated rounded class="mr-1 mb-2 w-24">
                  Secondary
                </Button>
                <Button variant="success" elevated rounded class="mr-1 mb-2 w-24">
                  Success
                </Button>
                <Button variant="warning" elevated rounded class="mr-1 mb-2 w-24">
                  Warning
                </Button>
                <Button variant="info" elevated rounded class="mr-1 mb-2 w-24">
                  Pending
                </Button>
                <Button variant="danger" elevated rounded class="mr-1 mb-2 w-24">
                  Danger
                </Button>
                <Button variant="dark" elevated rounded class="mr-1 mb-2 w-24">
                  Dark
                </Button>
              </div>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
    </div>
    <div class="col-span-12 lg:col-span-6">
      <Preview class="intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Outline Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-6">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-6" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <Button variant="outline-primary" class="inline-block mr-1 mb-2 w-24">
              Primary
            </Button>
            <Button variant="outline-secondary" class="inline-block mr-1 mb-2 w-24">
              Secondary
            </Button>
            <Button variant="outline-success" class="inline-block mr-1 mb-2 w-24">
              Success
            </Button>
            <Button variant="outline-warning" class="inline-block mr-1 mb-2 w-24">
              Warning
            </Button>
            <Button variant="outline-info" class="inline-block mr-1 mb-2 w-24">
              Pending
            </Button>
            <Button variant="outline-danger" class="inline-block mr-1 mb-2 w-24">
              Danger
            </Button>
            <Button variant="outline-dark" class="inline-block mr-1 mb-2 w-24">
              Dark
            </Button>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <Button variant="outline-primary" class="inline-block mr-1 mb-2 w-24">
                Primary
              </Button>
              <Button variant="outline-secondary" class="inline-block mr-1 mb-2 w-24">
                Secondary
              </Button>
              <Button variant="outline-success" class="inline-block mr-1 mb-2 w-24">
                Success
              </Button>
              <Button variant="outline-warning" class="inline-block mr-1 mb-2 w-24">
                Warning
              </Button>
              <Button variant="outline-info" class="inline-block mr-1 mb-2 w-24">
                Pending
              </Button>
              <Button variant="outline-danger" class="inline-block mr-1 mb-2 w-24">
                Danger
              </Button>
              <Button variant="outline-dark" class="inline-block mr-1 mb-2 w-24">
                Dark
              </Button>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Loading State Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-7">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-7" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <Button variant="primary" class="mr-1 mb-2">
              Saving
              <LoadingIcon icon="oval" color="white" class="ml-2 w-4 h-4" />
            </Button>
            <Button variant="success" class="mr-1 mb-2">
              Adding
              <LoadingIcon icon="spinning-circles" color="white" class="ml-2 w-4 h-4" />
            </Button>
            <Button variant="warning" class="mr-1 mb-2">
              Loading
              <LoadingIcon icon="three-dots" color="1a202c" class="ml-2 w-4 h-4" />
            </Button>
            <Button variant="danger" class="mr-1 mb-2">
              Deleting
              <LoadingIcon icon="puff" color="white" class="ml-2 w-4 h-4" />
            </Button>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <Button variant="primary" class="mr-1 mb-2">
                Saving
                <LoadingIcon icon="oval" color="white" class="ml-2 w-4 h-4" />
              </Button>
              <Button variant="success" class="mr-1 mb-2">
                Adding
                <LoadingIcon icon="spinning-circles" color="white" class="ml-2 w-4 h-4" />
              </Button>
              <Button variant="warning" class="mr-1 mb-2">
                Loading
                <LoadingIcon icon="three-dots" color="1a202c" class="ml-2 w-4 h-4" />
              </Button>
              <Button variant="danger" class="mr-1 mb-2">
                Deleting
                <LoadingIcon icon="puff" color="white" class="ml-2 w-4 h-4" />
              </Button>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Rounded Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-8">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-8" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <Button variant="primary" rounded class="mr-1 mb-2 w-24">
              Primary
            </Button>
            <Button variant="secondary" rounded class="mr-1 mb-2 w-24">
              Secondary
            </Button>
            <Button variant="success" rounded class="mr-1 mb-2 w-24">
              Success
            </Button>
            <Button variant="warning" rounded class="mr-1 mb-2 w-24">
              Warning
            </Button>
            <Button variant="info" rounded class="mr-1 mb-2 w-24">
              Pending
            </Button>
            <Button variant="danger" rounded class="mr-1 mb-2 w-24">
              Danger
            </Button>
            <Button variant="dark" rounded class="mr-1 mb-2 w-24">
              Dark
            </Button>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <Button variant="primary" rounded class="mr-1 mb-2 w-24">
                Primary
              </Button>
              <Button variant="secondary" rounded class="mr-1 mb-2 w-24">
                Secondary
              </Button>
              <Button variant="success" rounded class="mr-1 mb-2 w-24">
                Success
              </Button>
              <Button variant="warning" rounded class="mr-1 mb-2 w-24">
                Warning
              </Button>
              <Button variant="info" rounded class="mr-1 mb-2 w-24">
                Pending
              </Button>
              <Button variant="danger" rounded class="mr-1 mb-2 w-24">
                Danger
              </Button>
              <Button variant="dark" rounded class="mr-1 mb-2 w-24">
                Dark
              </Button>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Soft Color Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-9">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-9" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <Button variant="soft-primary" rounded class="mr-1 mb-2 w-24">
              Primary
            </Button>
            <Button variant="soft-secondary" rounded class="mr-1 mb-2 w-24">
              Secondary
            </Button>
            <Button variant="soft-success" rounded class="mr-1 mb-2 w-24">
              Success
            </Button>
            <Button variant="soft-warning" rounded class="mr-1 mb-2 w-24">
              Warning
            </Button>
            <Button variant="soft-warning" rounded class="mr-1 mb-2 w-24">
              Pending
            </Button>
            <Button variant="soft-danger" rounded class="mr-1 mb-2 w-24">
              Danger
            </Button>
            <Button variant="soft-dark" rounded class="mr-1 mb-2 w-24">
              Dark
            </Button>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <Button variant="soft-primary" rounded class="mr-1 mb-2 w-24">
                Primary
              </Button>
              <Button variant="soft-secondary" rounded class="mr-1 mb-2 w-24">
                Secondary
              </Button>
              <Button variant="soft-success" rounded class="mr-1 mb-2 w-24">
                Success
              </Button>
              <Button variant="soft-warning" rounded class="mr-1 mb-2 w-24">
                Warning
              </Button>
              <Button variant="soft-warning" rounded class="mr-1 mb-2 w-24">
                Pending
              </Button>
              <Button variant="soft-danger" rounded class="mr-1 mb-2 w-24">
                Danger
              </Button>
              <Button variant="soft-dark" rounded class="mr-1 mb-2 w-24">
                Dark
              </Button>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Icon Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-10">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-10" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <div class="flex flex-wrap">
              <Button variant="primary" class="mr-2 mb-2 w-32">
                <Lucide icon="Activity" class="mr-2 w-4 h-4" /> Activity
              </Button>
              <Button variant="secondary" class="mr-2 mb-2 w-32">
                <Lucide icon="HardDrive" class="mr-2 w-4 h-4" /> Hard Drive
              </Button>
              <Button variant="success" class="mr-2 mb-2 w-32">
                <Lucide icon="Calendar" class="mr-2 w-4 h-4" /> Calendar
              </Button>
              <Button variant="warning" class="mr-2 mb-2 w-32">
                <Lucide icon="Share2" class="mr-2 w-4 h-4" /> Share
              </Button>
              <Button variant="info" class="mr-2 mb-2 w-32">
                <Lucide icon="Download" class="mr-2 w-4 h-4" /> Pending
              </Button>
              <Button variant="danger" class="mr-2 mb-2 w-32">
                <Lucide icon="Trash" class="mr-2 w-4 h-4" /> Trash
              </Button>
              <Button variant="dark" class="mr-2 mb-2 w-32">
                <Lucide icon="Image" class="mr-2 w-4 h-4" /> Image
              </Button>
            </div>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <div class="flex flex-wrap">
                <Button variant="primary" class="mr-2 mb-2 w-32">
                  <Lucide icon="Activity" class="mr-2 w-4 h-4" /> Activity
                </Button>
                <Button variant="secondary" class="mr-2 mb-2 w-32">
                  <Lucide icon="HardDrive" class="mr-2 w-4 h-4" /> Hard Drive
                </Button>
                <Button variant="success" class="mr-2 mb-2 w-32">
                  <Lucide icon="Calendar" class="mr-2 w-4 h-4" /> Calendar
                </Button>
                <Button variant="warning" class="mr-2 mb-2 w-32">
                  <Lucide icon="Share2" class="mr-2 w-4 h-4" /> Share
                </Button>
                <Button variant="info" class="mr-2 mb-2 w-32">
                  <Lucide icon="Download" class="mr-2 w-4 h-4" /> Pending
                </Button>
                <Button variant="danger" class="mr-2 mb-2 w-32">
                  <Lucide icon="Trash" class="mr-2 w-4 h-4" /> Trash
                </Button>
                <Button variant="dark" class="mr-2 mb-2 w-32">
                  <Lucide icon="Image" class="mr-2 w-4 h-4" /> Image
                </Button>
              </div>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
      <Preview class="mt-5 intro-y box" v-slot="{ toggle }">
        <div class="flex sm:flex-row flex-col items-center p-5 border-slate-200/60 dark:border-darkmode-400 border-b">
          <h2 class="mr-auto font-medium text-base">Icon Only Buttons</h2>
          <FormSwitch class="mt-3 sm:mt-0 sm:ml-auto w-full sm:w-auto">
            <FormSwitch.Label htmlFor="show-example-11">
              Show example code
            </FormSwitch.Label>
            <FormSwitch.Input id="show-example-11" @click="toggle" class="mr-0 ml-3" type="checkbox" />
          </FormSwitch>
        </div>
        <div class="p-5">
          <Preview.Panel>
            <Button variant="primary" class="mr-1 mb-2">
              <Lucide icon="Activity" class="w-5 h-5" />
            </Button>
            <Button variant="secondary" class="mr-1 mb-2">
              <Lucide icon="HardDrive" class="w-5 h-5" />
            </Button>
            <Button variant="success" class="mr-1 mb-2">
              <Lucide icon="Calendar" class="w-5 h-5" />
            </Button>
            <Button variant="warning" class="mr-1 mb-2">
              <Lucide icon="Share2" class="w-5 h-5" />
            </Button>
            <Button variant="info" class="mr-1 mb-2">
              <Lucide icon="Download" class="w-5 h-5" />
            </Button>
            <Button variant="danger" class="mr-1 mb-2">
              <Lucide icon="Trash" class="w-5 h-5" />
            </Button>
            <Button variant="dark" class="mr-1 mb-2">
              <Lucide icon="Image" class="w-5 h-5" />
            </Button>
          </Preview.Panel>
          <Preview.Panel type="source">
            <Preview.Highlight>
              {{ `
              <Button variant="primary" class="mr-1 mb-2">
                <Lucide icon="Activity" class="w-5 h-5" />
              </Button>
              <Button variant="secondary" class="mr-1 mb-2">
                <Lucide icon="HardDrive" class="w-5 h-5" />
              </Button>
              <Button variant="success" class="mr-1 mb-2">
                <Lucide icon="Calendar" class="w-5 h-5" />
              </Button>
              <Button variant="warning" class="mr-1 mb-2">
                <Lucide icon="Share2" class="w-5 h-5" />
              </Button>
              <Button variant="info" class="mr-1 mb-2">
                <Lucide icon="Download" class="w-5 h-5" />
              </Button>
              <Button variant="danger" class="mr-1 mb-2">
                <Lucide icon="Trash" class="w-5 h-5" />
              </Button>
              <Button variant="dark" class="mr-1 mb-2">
                <Lucide icon="Image" class="w-5 h-5" />
              </Button>
              `}}
            </Preview.Highlight>
          </Preview.Panel>
        </div>
      </Preview>
    </div>
  </div>
</template>
