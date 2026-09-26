"use client"

import { QueryClientProvider } from "@tanstack/react-query"
import { ReactQueryDevtools } from "@tanstack/react-query-devtools"
import type { ReactNode } from "react"
import { ThemeProvider } from "@/components/theme-provider"
import { Toaster } from "@/components/ui/toast"
import { TooltipProvider } from "@/components/ui/tooltip"
import { AuthProvider } from "@/features/auth/hooks/auth-provider"
import type { AuthUser } from "@/features/auth/types"
import { getQueryClient } from "@/libs/query-client"

interface ProvidersProps {
  children: ReactNode
  initialUser: AuthUser | null
}

function Providers({ children, initialUser }: ProvidersProps) {
  const queryClient = getQueryClient()

  return (
    <ThemeProvider>
      <QueryClientProvider client={queryClient}>
        <AuthProvider initialUser={initialUser}>
          <TooltipProvider>
            {children}
            <Toaster />
          </TooltipProvider>
          {process.env.NODE_ENV === "development" && (
            <ReactQueryDevtools initialIsOpen={false} />
          )}
        </AuthProvider>
      </QueryClientProvider>
    </ThemeProvider>
  )
}

export default Providers
