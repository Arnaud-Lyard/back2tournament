"use client"

import { CheckIcon, CopyIcon } from "lucide-react"
import { useTranslations } from "next-intl"
import { Button } from "@/components/ui/button"
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip"
import { useCopyToClipboard } from "@/hooks/use-copy-to-clipboard"

interface CopyButtonProps {
  value: string
  label: string
}

export function CopyButton({ value, label }: CopyButtonProps) {
  const t = useTranslations("common")
  const { copied, copy } = useCopyToClipboard()

  return (
    <Tooltip>
      <TooltipTrigger
        render={
          <Button
            variant="ghost"
            size="icon-xs"
            onClick={() => void copy(value)}
          />
        }
      >
        {copied ? <CheckIcon /> : <CopyIcon />}
        <span className="sr-only">{label}</span>
      </TooltipTrigger>
      <TooltipContent>{copied ? t("copied") : label}</TooltipContent>
    </Tooltip>
  )
}
