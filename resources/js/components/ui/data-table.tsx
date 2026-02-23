import {
  type ColumnDef,
  flexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  type PaginationState,
  type RowData,
  type SortingState,
  useReactTable,
} from "@tanstack/react-table"
import * as React from "react"

import { Button } from "@/components/ui/button"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { cn } from "@/lib/utils"

declare module "@tanstack/react-table" {
  interface ColumnMeta<TData extends RowData, TValue> {
    headerClassName?: string
    cellClassName?: string
    _typedValue?: [TData, TValue]
  }
}

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[]
  data: TData[]
  emptyMessage?: string
  emptyState?: React.ReactNode
  className?: string
  tableClassName?: string
  showPagination?: boolean
  initialPageSize?: number
  pageSizeOptions?: number[]
}

export function DataTable<TData, TValue>({
  columns,
  data,
  emptyMessage = "No results found.",
  emptyState,
  className,
  tableClassName,
  showPagination = true,
  initialPageSize = 10,
  pageSizeOptions = [5, 10, 20, 50],
}: DataTableProps<TData, TValue>) {
  const [sorting, setSorting] = React.useState<SortingState>([])
  const [pagination, setPagination] = React.useState<PaginationState>({
    pageIndex: 0,
    pageSize: initialPageSize,
  })

  React.useEffect(() => {
    setPagination((previous) => ({
      ...previous,
      pageIndex: 0,
    }))
  }, [data.length])

  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    onSortingChange: setSorting,
    onPaginationChange: setPagination,
    state: {
      sorting,
      pagination,
    },
  })

  const totalRows = table.getSortedRowModel().rows.length
  const currentPageRows = table.getRowModel().rows.length
  const currentPage = table.getState().pagination.pageIndex + 1
  const totalPages = table.getPageCount()
  const startRow =
    totalRows === 0
      ? 0
      : table.getState().pagination.pageIndex * table.getState().pagination.pageSize +
        1
  const endRow = totalRows === 0 ? 0 : startRow + currentPageRows - 1

  return (
    <div className={cn("flex flex-col", className)}>
      <Table className={tableClassName}>
        <TableHeader>
          {table.getHeaderGroups().map((headerGroup) => (
            <TableRow key={headerGroup.id}>
              {headerGroup.headers.map((header) => (
                <TableHead
                  key={header.id}
                  className={cn(header.column.columnDef.meta?.headerClassName)}
                >
                  {header.isPlaceholder
                    ? null
                    : flexRender(
                        header.column.columnDef.header,
                        header.getContext(),
                      )}
                </TableHead>
              ))}
            </TableRow>
          ))}
        </TableHeader>
        <TableBody>
          {table.getRowModel().rows.length > 0 ? (
            table.getRowModel().rows.map((row) => (
              <TableRow key={row.id}>
                {row.getVisibleCells().map((cell) => (
                  <TableCell
                    key={cell.id}
                    className={cn(cell.column.columnDef.meta?.cellClassName)}
                  >
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </TableCell>
                ))}
              </TableRow>
            ))
          ) : (
            <TableRow>
              <TableCell
                colSpan={columns.length}
                className="h-24 text-center text-sm text-muted-foreground"
              >
                {emptyState ?? emptyMessage}
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>

      {showPagination && totalRows > 0 && (
        <div className="flex flex-col gap-3 border-t px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
          <p className="text-sm text-muted-foreground">
            Showing {startRow}-{endRow} of {totalRows}
          </p>
          <div className="flex items-center gap-2">
            <Select
              value={String(table.getState().pagination.pageSize)}
              onValueChange={(value) => table.setPageSize(Number(value))}
            >
              <SelectTrigger className="w-[94px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {pageSizeOptions.map((pageSizeOption) => (
                  <SelectItem
                    key={pageSizeOption}
                    value={String(pageSizeOption)}
                  >
                    {pageSizeOption}/pg
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <span className="text-sm text-muted-foreground">
              Page {currentPage} of {totalPages}
            </span>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.previousPage()}
              disabled={!table.getCanPreviousPage()}
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => table.nextPage()}
              disabled={!table.getCanNextPage()}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
