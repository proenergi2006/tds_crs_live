import { extendTailwindMerge } from "tailwind-merge";

// Without these groups tailwind-merge reads any unknown `text-*` as a text color,
// so `twMerge("text-body text-slate-500")` would silently drop `text-body`.
export const twMerge = extendTailwindMerge({
  extend: {
    classGroups: {
      "text-style": [
        {
          text: [
            "screen-title",
            "section-title",
            "card-title",
            "overline",
            "form-label",
            "body-lg",
            "body",
            "body-strong",
            "caption",
          ],
        },
      ],
      "num-style": [{ num: ["micro", "sm", "md", "lg", "display"] }],
    },
  },
});
