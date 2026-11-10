import { Suspense } from "react";
import OsszApp from "@/components/ossz/ossz-app";
import { Skeleton } from "@/components/ui/skeleton";

export default function Home() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex flex-col items-center justify-center gap-4 bg-background">
          <div className="size-16 rounded-2xl bg-primary/10 flex items-center justify-center">
            <span className="font-display text-3xl font-bold text-primary">O</span>
          </div>
          <Skeleton className="h-4 w-48" />
        </div>
      }
    >
      <OsszApp />
    </Suspense>
  );
}
