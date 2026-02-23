import type { Column } from "@tanstack/react-table"
import { ArrowDown, ArrowUp, ArrowUpDown } from "lucide-react"

import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"

interface DataTableColumnHeaderProps<TData, TValue> {
  column: Column<TData, TValue>
  title: string
  className?: string
}

export function DataTableColumnHeader<TData, TValue>({
  column,
  title,
  className,
}: DataTableColumnHeaderProps<TData, TValue>) {
  if (!column.getCanSort()) {
    return <span className={className}>{title}</span>
  }

  const sorted = column.getIsSorted()

  return (
    <Button
      variant="ghost"
      size="sm"
      className={cn("h-8 px-2", className)}
      onClick={() => column.toggleSorting(sorted === "asc")}
    >
      <span>{title}</span>
      {sorted === "asc" ? (
        <ArrowUp className="ml-2 size-4" />
      ) : sorted === "desc" ? (
        <ArrowDown className="ml-2 size-4" />
      ) : (
        <ArrowUpDown className="ml-2 size-4" />
      )}
    </Button>
  )
}
