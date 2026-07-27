<template>
  <div class="grid min-h-screen grid-cols-1 md:grid-cols-[320px_1fr]">
    <div class="bg-slate-900">
      <slot name="sidebar" />
    </div>

    <div class="relative min-h-0 max-h-screen rounded-xl overflow-y-auto bg-slate-50 px-5 py-8 md:px-10">
      <slot name="content" />
    </div>
  </div>
</template>
