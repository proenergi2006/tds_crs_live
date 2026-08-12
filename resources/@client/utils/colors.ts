import { toRGB } from "./helper";
import tailwindColors from "tailwindcss/colors";
import resolveConfig from "tailwindcss/resolveConfig";
import tailwindConfig from "tailwind-config";
import { flatten } from "flat";

const twConfig = resolveConfig(tailwindConfig);
const colors = twConfig.theme?.colors;

type DefaultColors = typeof tailwindColors;

/** Extended colors */
interface Colors extends DefaultColors {
  primary: string;
  secondary: string;
  success: string;
  info: string;
  warning: string;
  pending: string;
  danger: string;
  light: string;
  dark: string;
  darkmode: {
    50: string;
    100: string;
    200: string;
    300: string;
    400: string;
    500: string;
    600: string;
    700: string;
    800: string;
    900: string;
  };
}

/** Get a value from Tailwind colors by flatten index, if not available the value will be taken from the CSS variable with (--color-) prefix. */
const getColor = (colorKey: DotNestedKeys<Colors>, opacity: number = 1) => {
  const flattenColors = flatten<
    typeof colors,
    {
      [key: string]: string;
    }
  >(colors);

  if (flattenColors[colorKey].search("var") === -1) {
    return `rgb(${toRGB(flattenColors[colorKey])} / ${opacity})`;
  } else {
    const cssVariableName = `--color-${
      flattenColors[colorKey].split("--color-")[1].split(")")[0]
    }`;
    return `rgb(${getComputedStyle(document.body).getPropertyValue(
      cssVariableName
    )} / ${opacity})`;
  }
};

// warna ke-N dipasang ke slice dengan RANK ke-N (slice terbesar dapet warna pertama), bukan urutan kategori di data
const DONUT_COLOR_PALETTE: Parameters<typeof getColor>[0][] = [
  "primary",
  "emerald.400",
  "amber.400",
  "sky.400",
  "rose.400",
  "violet.400",
  "cyan.400",
  "lime.400",
  "fuchsia.400",
  "slate.400",
];

// hasil array tetap sejajar urutan `values` asli (bukan hasil sort), jadi bisa langsung dipasang ke backgroundColor
const getDonutColors = (values: number[], opacity: number = 0.85): string[] => {
  const rankedIndexes = values
    .map((value, index) => ({ value, index }))
    .sort((a, b) => b.value - a.value);

  const colorByIndex = new Array<string>(values.length);
  rankedIndexes.forEach(({ index }, rank) => {
    colorByIndex[index] = getColor(DONUT_COLOR_PALETTE[rank % DONUT_COLOR_PALETTE.length], opacity);
  });
  return colorByIndex;
};

export { getColor, getDonutColors };
