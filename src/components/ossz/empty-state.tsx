"use client";

import type { ReactNode } from "react";
import { cn } from "@/lib/utils";

export function EmptyState({
  icon,
  title,
  description,
  action,
  className,
}: {
  icon?: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn("flex flex-col items-center justify-center gap-3 py-12 px-6 text-center", className)}>
      {icon && <div className="text-muted-foreground/60 [&>svg]:size-10">{icon}</div>}
      <div>
        <p className="font-medium">{title}</p>
        {description && (
          <p className="text-sm text-muted-foreground mt-1 max-w-sm mx-auto">{description}</p>
        )}
      </div>
      {action}
    </div>
  );
}
