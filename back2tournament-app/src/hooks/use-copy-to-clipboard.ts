"use client"

import { useCallback, useEffect, useRef, useState } from "react"

const COPIED_FEEDBACK_MS = 2000

/** Copies text and reports `copied` for a moment, to swap the button's icon. */
export function useCopyToClipboard() {
  const [copied, setCopied] = useState(false)
  const resetTimer = useRef<ReturnType<typeof setTimeout>>(undefined)

  useEffect(() => () => clearTimeout(resetTimer.current), [])

  const copy = useCallback(async (text: string) => {
    try {
      await navigator.clipboard.writeText(text)
    } catch {
      return false
    }

    setCopied(true)
    clearTimeout(resetTimer.current)
    resetTimer.current = setTimeout(() => setCopied(false), COPIED_FEEDBACK_MS)
    return true
  }, [])

  return { copied, copy }
}
