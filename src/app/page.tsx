import AtelierApp from "@/components/atelier/atelier-app";
import { Scissors } from "lucide-react";

export default function Home() {
  return (
    <div className="min-h-screen bg-background">
      <AtelierApp />
      <noscript>
        <div className="min-h-screen flex flex-col items-center justify-center gap-4">
          <Scissors className="size-10 text-primary" />
          <p className="text-lg font-semibold">OSSZ Atelier requires JavaScript.</p>
        </div>
      </noscript>
    </div>
  );
}
