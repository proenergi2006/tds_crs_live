import {
  type TomSelectProps,
  type TomSelectElement,
  type TomSelectEmit,
} from "./TomSelect.vue";
import {
  type TomSettings,
  type RecursivePartial,
} from "tom-select/src/types/index";
import TomSelect from "tom-select";
import _ from "lodash";

const setValue = (el: TomSelectElement, props: TomSelectProps) => {
  if (Array.isArray(props.modelValue)) {
    if (!props.modelValue.length) {
      el.selectedIndex = -1;
      return;
    }

    for (const value of props.modelValue) {
      const selectedOption = Array.from(el).find(
        (option) =>
          option instanceof HTMLOptionElement && option.value == value
      );

      if (
        selectedOption !== undefined &&
        selectedOption instanceof HTMLOptionElement
      ) {
        selectedOption.selected = true;
      }
    }
  } else if (props.modelValue) {
    el.value = props.modelValue;
  } else {
    el.selectedIndex = -1;
  }
};

const init = (
  originalEl: TomSelectElement,
  clonedEl: TomSelectElement,
  props: TomSelectProps,
  computedOptions: RecursivePartial<TomSettings>,
  emit: TomSelectEmit
) => {
  computedOptions = {
    onOptionAdd: function (value: string | number) {
      const newOption = document.createElement("option");
      newOption.value = value.toString();
      newOption.text = value.toString();
      originalEl.add(newOption);

      emit("optionAdd", value);
    },
    ...computedOptions,
  };

  clonedEl.TomSelect = new TomSelect(clonedEl, computedOptions);

  clonedEl.TomSelect.on("change", function (selectedItems: string[] | string) {
    emit(
      "update:modelValue",
      Array.isArray(selectedItems) ? [...selectedItems] : selectedItems
    );
  });
};

const getOptions = (
  options: HTMLCollection | undefined,
  tempOptions: Element[] = []
) => {
  if (options) {
    Array.from(options).forEach(function (optionEl) {
      if (optionEl instanceof HTMLOptGroupElement) {
        getOptions(optionEl.children, tempOptions);
      } else {
        tempOptions.push(optionEl);
      }
    });
  }

  return tempOptions;
};

const updateValue = (
  originalEl: TomSelectElement,
  clonedEl: TomSelectElement,
  value: string | string[],
  props: TomSelectProps,
  computedOptions: RecursivePartial<TomSettings>,
  emit: TomSelectEmit
) => {
  for (const [optionKey, option] of Object.entries(
    clonedEl.TomSelect.options
  )) {
    if (
      !getOptions(originalEl.children).filter((optionEl) => {
        return (
          optionEl instanceof HTMLOptionElement &&
          optionEl.value === option.value
        );
      }).length
    ) {
      clonedEl.TomSelect.removeOption(option.value);
    }
  }

  const initialClassNames = clonedEl
    .getAttribute("data-initial-class")
    ?.split(" ");
  clonedEl.setAttribute(
    "class",
    [
      ...Array.from(originalEl.classList),
      ...Array.from(clonedEl.classList).filter(
        (className) => initialClassNames?.indexOf(className) == -1
      ),
    ].join(" ")
  );
  clonedEl.TomSelect.wrapper.setAttribute(
    "class",
    [
      ...Array.from(originalEl.classList),
      ...Array.from(clonedEl.TomSelect.wrapper.classList).filter(
        (className) => initialClassNames?.indexOf(className) == -1
      ),
    ].join(" ")
  );
  clonedEl.setAttribute(
    "data-initial-class",
    Array.from(originalEl.classList).join(" ")
  );

  const options = originalEl.children;
  if (options) {
    const allowEmptyOption = (computedOptions as any)?.allowEmptyOption === true;
    Array.from(options).forEach(function (optionEl) {
      const optionValue = optionEl.getAttribute("value");
      if (!optionValue && !allowEmptyOption) return;
      clonedEl.TomSelect.addOption({
        text: optionEl.textContent,
        value: optionValue,
      });
    });
  }

  clonedEl.TomSelect.refreshOptions(false);

  if (
    (!Array.isArray(value) && value !== clonedEl.TomSelect.getValue()) ||
    (Array.isArray(value) && !_.isEqual(value, clonedEl.TomSelect.getValue()))
  ) {
    clonedEl.TomSelect.destroy();
    if (originalEl.innerHTML) {
      clonedEl.innerHTML = originalEl.innerHTML;
    }
    setValue(clonedEl, props);
    init(originalEl, clonedEl, props, computedOptions, emit);
  }
};

export { setValue, init, updateValue };
