import { QueryClient, QueryClientProvider } from "@tanstack/react-query"
import { render, type RenderOptions } from "@testing-library/react"
import { NextIntlClientProvider } from "next-intl"
import type { ReactElement, ReactNode } from "react"
import { ThemeProvider } from "@/components/theme-provider"
import { TooltipProvider } from "@/components/ui/tooltip"
import { AuthProvider } from "@/features/auth/hooks/auth-provider"
import type { AuthUser } from "@/features/auth/types"
import messages from "@/messages/en.json"

interface RenderWithProvidersOptions extends RenderOptions {
  user?: AuthUser | null
}

function Wrapper({
  children,
  user = null,
}: {
  children: ReactNode
  user?: AuthUser | null
}) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })

  return (
    <NextIntlClientProvider locale="en" messages={messages}>
      <ThemeProvider
        attribute="class"
        defaultTheme="light"
        enableSystem={false}
      >
        <QueryClientProvider client={queryClient}>
          <AuthProvider initialUser={user}>
            <TooltipProvider>{children}</TooltipProvider>
          </AuthProvider>
        </QueryClientProvider>
      </ThemeProvider>
    </NextIntlClientProvider>
  )
}

export function renderWithProviders(
  ui: ReactElement,
  { user, ...options }: RenderWithProvidersOptions = {}
) {
  return render(ui, {
    wrapper: ({ children }) => <Wrapper user={user}>{children}</Wrapper>,
    ...options,
  })
}

export * from "@testing-library/react"
