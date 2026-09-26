import type messages from "./messages/fr.json"

// Types every `t()` key against the default locale's messages; the parity
// test in src/messages keeps the other locales in step.
declare module "next-intl" {
  interface AppConfig {
    Messages: typeof messages
  }
}
