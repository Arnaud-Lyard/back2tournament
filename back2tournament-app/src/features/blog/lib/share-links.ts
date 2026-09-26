export type ShareNetwork = "facebook" | "linkedin" | "twitter"

export function shareLink(
  network: ShareNetwork,
  url: string,
  title: string
): string {
  const target = encodeURIComponent(url)
  const text = encodeURIComponent(title)

  switch (network) {
    case "facebook":
      return `https://www.facebook.com/sharer/sharer.php?u=${target}`
    case "linkedin":
      return `https://www.linkedin.com/sharing/share-offsite/?url=${target}`
    case "twitter":
      return `https://twitter.com/intent/tweet?url=${target}&text=${text}`
  }
}
