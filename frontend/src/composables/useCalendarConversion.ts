import { useI18n } from "vue-i18n";

interface JalaliDate {
  year: number;
  month: number;
  day: number;
}

export function useCalendarConversion() {
  const { locale } = useI18n();

  /**
   * Convert Gregorian date to Jalali
   */
  const convertToJalali = (date: Date): JalaliDate => {
    let gy = date.getFullYear();
    const gm = date.getMonth() + 1;
    const gd = date.getDate();

    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

    let jy = gy <= 1600 ? 0 : 979;
    gy > 1600 && (gy -= 1600);
    gy > 1600 && (jy += 33 * Math.floor(gy / 33));
    gy > 1600 && (gy %= 33);

    let jp = 0;
    for (let i = 0; i < gy; i++) {
      jp += isGregorianLeapYear(1600 + i) ? 366 : 365;
    }

    if (gm > 2) {
      jp += g_d_m[gm - 1];
    } else {
      jp += g_d_m[gm - 1];
    }

    if (gm > 2 && isGregorianLeapYear(gy + 1600)) {
      jp++;
    }

    jp += gd;

    let jd = jp - 79;

    const j_np = Math.floor(jd / 12053);
    jd %= 12053;

    jy += 33 * j_np;

    const a = Math.floor(jd / 1029);
    let b = jd % 1029;

    if (a >= 33) {
      const c = Math.floor((a - 33) / 128);
      const d = (a - 33) % 128;
      jy += 128 * c + 29 * d;
      if (d >= 29) {
        jy++;
        b += 366;
      }
    } else {
      jy += 29 * a;
      if (a >= 29) {
        b += 366;
      }
    }

    if (b >= 366) {
      jy++;
      b -= 366;
    }

    let jm: number;
    if (b < 186) {
      jm = 1 + Math.floor(b / 31);
      jd = 1 + (b % 31);
    } else {
      jm = 7 + Math.floor((b - 186) / 30);
      jd = 1 + ((b - 186) % 30);
    }

    return { year: jy, month: jm, day: jd };
  };

  /**
   * Convert Jalali date to Gregorian
   */
  const convertToGregorian = (jy: number, jm: number, jd: number): Date => {
    let gy = jy <= 979 ? 1600 : 1979;
    jy > 979 && (jy -= 979);
    jy > 979 && (gy += 400 * Math.floor(jy / 1029));
    jy > 979 && (jy %= 1029);

    let jp = 0;

    if (jy >= 29) {
      const cycles = Math.floor(jy / 33);
      const cyear = jy % 33;
      jp += cycles * 12053;

      if (cyear >= 29) {
        jp += 366;
        jy = cyear - 29;
      } else {
        jy = cyear;
      }

      if (jy >= 1) {
        jp += Math.floor((jy - 1) / 4) * 1461;
        jy = (jy - 1) % 4;

        if (jy >= 1) {
          jp += (jy - 1) * 365 + Math.floor(jy / 4);
        }
      }
    } else {
      jp += jy * 365 + Math.floor(jy / 4);
    }

    if (jm < 7) {
      jp += (jm - 1) * 31;
    } else {
      jp += (jm - 7) * 30 + 186;
    }

    jp += jd - 1;

    let gd = jp + 79;

    const cycles = Math.floor(gd / 146097);
    gd %= 146097;
    gy += cycles * 400;

    let tmp = Math.floor(gd / 36524);
    if (tmp >= 4) tmp = 3;
    gd -= tmp * 36524;
    gy += tmp * 100;

    tmp = Math.floor(gd / 1461);
    gd %= 1461;
    gy += tmp * 4;

    tmp = Math.floor(gd / 365);
    if (tmp >= 4) tmp = 3;
    gd -= tmp * 365;
    gy += tmp;

    const sal_a = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    if (isGregorianLeapYear(gy)) {
      sal_a[2] = 29;
    }

    let gm = 0;
    while (gm < 13 && gd >= sal_a[gm]) {
      gd -= sal_a[gm];
      gm++;
    }

    if (gm > 12) {
      gm = 12;
      gd = sal_a[12];
    }

    return new Date(gy, gm - 1, gd + 1);
  };

  /**
   * Check if Gregorian year is leap year
   */
  const isGregorianLeapYear = (year: number): boolean => {
    return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
  };

  /**
   * Check if Jalali year is leap year
   */
  const isJalaliLeapYear = (year: number): boolean => {
    const breaks = [
      -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097,
      2192, 2262, 2324, 2394, 2456, 3178,
    ];

    let jp = breaks[0];
    let jump = 0;
    for (let j = 1; j <= breaks.length; j++) {
      const jm = breaks[j];
      jump = jm - jp;
      if (year < jm) break;
      jp = jm;
    }

    let n = year - jp;
    if (n < jump) {
      n = n - Math.floor(n / 33) * 33;
      return n % 4 === 1;
    }

    return false;
  };

  /**
   * Format Jalali date
   */
  const formatJalaliDate = (
    date: Date,
    format: string = "YYYY/MM/DD",
  ): string => {
    const jalali = convertToJalali(date);

    return format
      .replace("YYYY", jalali.year.toString())
      .replace("YY", jalali.year.toString().slice(-2))
      .replace("MM", jalali.month.toString().padStart(2, "0"))
      .replace("M", jalali.month.toString())
      .replace("DD", jalali.day.toString().padStart(2, "0"))
      .replace("D", jalali.day.toString());
  };

  /**
   * Format Gregorian date
   */
  const formatGregorianDate = (
    date: Date,
    format: string = "YYYY/MM/DD",
  ): string => {
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    const day = date.getDate();

    return format
      .replace("YYYY", year.toString())
      .replace("YY", year.toString().slice(-2))
      .replace("MM", month.toString().padStart(2, "0"))
      .replace("M", month.toString())
      .replace("DD", day.toString().padStart(2, "0"))
      .replace("D", day.toString());
  };

  /**
   * Get current date in preferred calendar system
   */
  const getCurrentDate = (): Date => {
    return new Date();
  };

  /**
   * Parse date string based on current locale
   */
  const parseDate = (dateString: string): Date | null => {
    if (!dateString) return null;

    try {
      if (locale.value === "fa") {
        // Parse Jalali date format (YYYY/MM/DD)
        const parts = dateString.split("/");
        if (parts.length === 3) {
          const year = parseInt(parts[0]);
          const month = parseInt(parts[1]);
          const day = parseInt(parts[2]);
          return convertToGregorian(year, month, day);
        }
      } else {
        // Parse Gregorian date
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
          return date;
        }
      }
    } catch (error) {
      console.error("Error parsing date:", error);
    }

    return null;
  };

  /**
   * Format date based on current locale
   */
  const formatDate = (date: Date | string, format?: string): string => {
    if (!date) return "";

    // Convert string to Date if needed
    const dateObj = typeof date === "string" ? new Date(date) : date;

    // Check if date is valid
    if (isNaN(dateObj.getTime())) return "";

    if (locale.value === "fa") {
      return formatJalaliDate(dateObj, format);
    } else {
      return formatGregorianDate(dateObj, format);
    }
  };

  /**
   * Get month names for current locale
   */
  const getMonthNames = (): string[] => {
    if (locale.value === "fa") {
      return [
        "فروردین",
        "اردیبهشت",
        "خرداد",
        "تیر",
        "مرداد",
        "شهریور",
        "مهر",
        "آبان",
        "آذر",
        "دی",
        "بهمن",
        "اسفند",
      ];
    } else {
      return [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ];
    }
  };

  /**
   * Get weekday names for current locale
   */
  const getWeekdayNames = (): string[] => {
    if (locale.value === "fa") {
      return [
        "شنبه",
        "یکشنبه",
        "دوشنبه",
        "سه‌شنبه",
        "چهارشنبه",
        "پنج‌شنبه",
        "جمعه",
      ];
    } else {
      return [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
      ];
    }
  };

  return {
    convertToJalali,
    convertToGregorian,
    isGregorianLeapYear,
    isJalaliLeapYear,
    formatJalaliDate,
    formatGregorianDate,
    getCurrentDate,
    parseDate,
    formatDate,
    getMonthNames,
    getWeekdayNames,
  };
}
