import { Avatar as AvatarPrimitive } from "radix-ui"
import * as React from "react"

import { cn } from "@/lib/utils"

function Avatar({
  className,
  size = "default",
  ...props
}: React.ComponentProps<typeof AvatarPrimitive.Root> & {
  size?: "default" | "sm" | "lg" | "xl" | "2xl"
}) {
  return (
    <AvatarPrimitive.Root
      data-slot="avatar"
      data-size={size}
      className={cn(
        "group/avatar relative flex size-8 shrink-0 overflow-hidden rounded-full select-none data-[size=sm]:size-6 data-[size=lg]:size-10 data-[size=xl]:size-12 data-[size=2xl]:size-16",
        className
      )}
      {...props}
    />
  )
}

function AvatarImage({
  className,
  ...props
}: React.ComponentProps<typeof AvatarPrimitive.Image>) {
  return (
    <AvatarPrimitive.Image
      data-slot="avatar-image"
      className={cn("aspect-square size-full", className)}
      {...props}
    />
  )
}

function AvatarFallback({
  className,
  children,
  ...props
}: React.ComponentProps<typeof AvatarPrimitive.Fallback>) {
  let colorClass = "bg-muted text-muted-foreground";

  let childrenString = '';
  if (typeof children === 'string') {
     childrenString = children.trim();
  } else if (typeof children === 'number') {
     childrenString = String(children);
  } else if (Array.isArray(children)) {
     childrenString = children.filter(c => typeof c === 'string' || typeof c === 'number').join('').trim();
  }

  if (childrenString.length > 0) {
    const bgColors = [
      'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400',
      'bg-orange-100 text-orange-700 dark:bg-orange-950/50 dark:text-orange-400',
      'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
      'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
      'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/50 dark:text-cyan-400',
      'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-400',
      'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400',
      'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-400',
      'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950/50 dark:text-fuchsia-400',
      'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400',
    ];
    let hash = 0;
    for (let i = 0; i < childrenString.length; i++) {
        hash = childrenString.charCodeAt(i) + ((hash << 5) - hash);
    }
    const colorIndex = Math.abs(hash) % bgColors.length;
    colorClass = bgColors[colorIndex];
  }

  return (
    <AvatarPrimitive.Fallback
      data-slot="avatar-fallback"
      className={cn(
        `flex size-full items-center justify-center font-medium rounded-full ${colorClass} text-sm group-data-[size=sm]/avatar:text-xs`,
        className
      )}
      {...props}
    >
      {children}
    </AvatarPrimitive.Fallback>
  )
}

function AvatarBadge({ className, ...props }: React.ComponentProps<"span">) {
  return (
    <span
      data-slot="avatar-badge"
      className={cn(
        "bg-primary text-primary-foreground ring-background absolute right-0 bottom-0 z-10 inline-flex items-center justify-center rounded-full ring-2 select-none",
        "group-data-[size=sm]/avatar:size-2 group-data-[size=sm]/avatar:[&>svg]:hidden",
        "group-data-[size=default]/avatar:size-2.5 group-data-[size=default]/avatar:[&>svg]:size-2",
        "group-data-[size=lg]/avatar:size-3 group-data-[size=lg]/avatar:[&>svg]:size-2",
        className
      )}
      {...props}
    />
  )
}

function AvatarGroup({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="avatar-group"
      className={cn(
        "*:data-[slot=avatar]:ring-background group/avatar-group flex -space-x-2 *:data-[slot=avatar]:ring-2",
        className
      )}
      {...props}
    />
  )
}

function AvatarGroupCount({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="avatar-group-count"
      className={cn(
        "bg-muted text-muted-foreground ring-background relative flex size-8 shrink-0 items-center justify-center rounded-full text-sm ring-2 group-has-data-[size=lg]/avatar-group:size-10 group-has-data-[size=sm]/avatar-group:size-6 [&>svg]:size-4 group-has-data-[size=lg]/avatar-group:[&>svg]:size-5 group-has-data-[size=sm]/avatar-group:[&>svg]:size-3",
        className
      )}
      {...props}
    />
  )
}

export {
  Avatar,
  AvatarImage,
  AvatarFallback,
  AvatarBadge,
  AvatarGroup,
  AvatarGroupCount,
}
