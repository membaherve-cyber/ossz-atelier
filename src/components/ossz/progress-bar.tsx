"use client";

import { Progress } from "@/components/ui/progress";
import { cn } from "@/lib/utils";

export function ProgressBar({
  value,
  className,
  showLabel = true,
}: {
  value: number;
  className?: string;
  showLabel?: boolean;
}) {
  const hue =
    value >= 100 ? "bg-green-600" : value >= 50 ? "bg-primary" : value > 0 ? "bg-amber-600" : "bg-stone-400";
  return (
    <div className={cn("flex items-center gap-2 min-w-24", className)}>
      <Progress value={value} className={cn("h-2 flex-1 [&>div]:", hue)} />
      {showLabel && (
        <span className="text-xs font-semibold tabular-nums text-muted-foreground w-10 text-right">
          {Math.round(value)}%
        </span>
      )}
    </div>
  );
}
