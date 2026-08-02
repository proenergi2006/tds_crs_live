import { type Ref, type UnwrapRef } from "vue";

export interface CkeditorElement extends HTMLDivElement {
  CKEditor: any;
}

export type CkeditorProps = {
  modelValue: string;
  config?: any;
  disabled?: boolean;
  refKey?: string;
};

export type CkeditorEmit = {
  (e: "update:modelValue", value: string): void;
  (e: "focus", value: string, editor: any): void;
  (e: "blur", value: string, editor: any): void;
  (e: "ready", editor: string): void;
};

const init = async (
  el: CkeditorElement,
  editorBuild: any,
  {
    props,
    emit,
    cacheData,
  }: {
    props: CkeditorProps;
    emit: CkeditorEmit;
    cacheData: Ref<UnwrapRef<string>>;
  }
) => {
  cacheData.value = props.modelValue;
  props.config.initialData = props.modelValue;

  const editor = await editorBuild.create(el, props.config);

  el.CKEditor = editor;

  props.disabled && editor.enableReadOnlyMode("ckeditor");

  editor.model.document.on("change:data", () => {
    const data = editor.getData();
    cacheData.value = data;
    emit("update:modelValue", data);
  });

  editor.editing.view.document.on("focus", (evt: any) => {
    emit("focus", evt, editor);
  });

  editor.editing.view.document.on("blur", (evt: any) => {
    emit("blur", evt, editor);
  });

  emit("ready", editor);
};

// Watch buat model + disabled change. init() cuma baca props.disabled
// sekali pas instance dibuat, gak dipanggil ulang lewat updated(). Kalau
// disabled berubah setelah mount (misalnya form ke-unlock begitu data
// async kelar load), instance CKEditor yang udah kadung read-only gak
// bakal ke-update -- user gak akan pernah bisa ngetik lagi meski prop-nya
// udah balik jadi false.
const updateData = (
  el: CkeditorElement,
  {
    props,
    cacheData,
  }: {
    props: CkeditorProps;
    cacheData: Ref<UnwrapRef<string>>;
  }
) => {
  if (!el.CKEditor) return;

  if (cacheData.value !== props.modelValue) {
    el.CKEditor.setData(props.modelValue);
  }

  if (props.disabled && !el.CKEditor.isReadOnly) {
    el.CKEditor.enableReadOnlyMode("ckeditor");
  } else if (!props.disabled && el.CKEditor.isReadOnly) {
    el.CKEditor.disableReadOnlyMode("ckeditor");
  }
};

export { init, updateData };
