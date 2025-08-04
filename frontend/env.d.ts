/// <reference types="vite/client" />

declare module "*.vue" {
  import type { DefineComponent } from "vue";
  const component: DefineComponent<{}, {}, any>;
  export default component;
}

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string;
  readonly VITE_APP_NAME: string;
  readonly VITE_APP_VERSION: string;
  readonly VITE_DEV_MODE: string;
  readonly VITE_DEBUG: string;
  readonly VITE_ENABLE_PWA: string;
  readonly VITE_ENABLE_DARK_MODE: string;
  readonly VITE_ENABLE_RTL: string;
  readonly VITE_WHATSAPP_API_URL: string;
  readonly VITE_SMS_API_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
