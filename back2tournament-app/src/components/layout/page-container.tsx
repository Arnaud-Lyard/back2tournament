import { cn } from "@/libs/utils"

export function PageContainer({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      className={cn(
        "mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-8 md:py-12",
        className
      )}
      {...props}
    />
  )
}
