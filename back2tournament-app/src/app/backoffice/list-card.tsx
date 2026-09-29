import { ListChecksIcon } from "lucide-react"
import type { ReactNode } from "react"
import { Badge } from "@/components/ui/badge"
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from "@/components/ui/empty"

interface ListCardProps {
  title: string
  description: string
  count: number
  showCount?: boolean
  emptyTitle: string
  emptyDescription: string
  children: ReactNode
}

export function ListCard({
  title,
  description,
  count,
  showCount = true,
  emptyTitle,
  emptyDescription,
  children,
}: ListCardProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
        {showCount && count > 0 && (
          <CardAction>
            <Badge variant="secondary">{count}</Badge>
          </CardAction>
        )}
      </CardHeader>
      <CardContent>
        {count === 0 ? (
          <Empty className="border">
            <EmptyHeader>
              <EmptyMedia variant="icon">
                <ListChecksIcon />
              </EmptyMedia>
              <EmptyTitle>{emptyTitle}</EmptyTitle>
              <EmptyDescription>{emptyDescription}</EmptyDescription>
            </EmptyHeader>
          </Empty>
        ) : (
          children
        )}
      </CardContent>
    </Card>
  )
}
