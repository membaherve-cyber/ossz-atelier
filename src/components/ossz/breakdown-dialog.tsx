"use client";

import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Plus, Trash2, Loader2, Scissors } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { api } from "./api-client";
import type { OrderDetail } from "./types";

type PieceDraft = { key: number; name: string; productionInstructions: string };

/**
 * Production breakdown dialog: create one production task per garment piece.
 * Pieces are pre-filled from the product components; authorized staff can
 * adjust names and instructions. Existing pieces are never duplicated —
 * the API skips pieces that already have tasks.
 */
export function BreakdownDialog({
  orderId,
  items,
}: {
  orderId: string;
  items: OrderDetail["order"]["items"];
}) {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(false);

  const [draft, setDraft] = useState<Record<string, PieceDraft[]>>({});

  // Pre-fill pieces per order item from product components (or single piece per unit).
  function buildDraft(): Record<string, PieceDraft[]> {
    const result: Record<string, PieceDraft[]> = {};
    for (const item of items) {
      const pieces: PieceDraft[] = [];
      for (let unit = 1; unit <= item.quantity; unit++) {
        if (item.productComponents.length > 0) {
          for (const comp of item.productComponents) {
            pieces.push({
              key: pieces.length,
              name: item.quantity > 1 ? `${comp} #${unit}` : comp,
              productionInstructions: "",
            });
          }
        } else {
          pieces.push({
            key: pieces.length,
            name: item.quantity > 1 ? `${item.productName} #${unit}` : item.productName,
            productionInstructions: "",
          });
        }
      }
      result[item.id] = pieces;
    }
    return result;
  }

  const effectiveDraft: Record<string, PieceDraft[]> =
    Object.keys(draft).length > 0 ? draft : buildDraft();

  const createBreakdown = useMutation({
    mutationFn: (payload: unknown) =>
      api.post<{ createdCount: number; skippedCount: number }>(
        `/api/orders/${orderId}/production-tasks`,
        payload
      ),
    onSuccess: (res) => {
      toast({
        title: "Production breakdown created",
        description: `${res.createdCount} task(s) created${res.skippedCount > 0 ? `, ${res.skippedCount} already existed and were skipped` : ""}.`,
      });
      queryClient.invalidateQueries({ queryKey: ["order", orderId] });
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      queryClient.invalidateQueries({ queryKey: ["production-tasks"] });
      setOpen(false);
      setDraft({});
    },
    onError: (err: Error) => {
      toast({ title: "Could not create breakdown", description: err.message, variant: "destructive" });
    },
  });

  const totalPieces = Object.values(effectiveDraft).reduce((s, arr) => s + arr.length, 0);

  function submit() {
    createBreakdown.mutate({
      items: Object.entries(effectiveDraft).map(([orderItemId, pieces]) => ({
        orderItemId,
        pieces: pieces
          .filter((p) => p.name.trim())
          .map((p) => ({
            name: p.name.trim(),
            productionInstructions: p.productionInstructions.trim() || undefined,
          })),
      })),
    });
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Scissors className="size-4" /> Create Production Breakdown
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="font-display">Production Breakdown</DialogTitle>
          <DialogDescription>
            Each garment piece becomes its own production task linked to this order. Pieces already
            having tasks are skipped automatically — nothing is duplicated.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-5">
          {items.map((item) => (
            <div key={item.id} className="space-y-2">
              <div className="flex items-center justify-between">
                <p className="font-semibold">
                  {item.productName}
                  {item.quantity > 1 && <Badge variant="secondary" className="ml-2">×{item.quantity}</Badge>}
                </p>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() =>
                    setDraft((prev) => {
                      const base = Object.keys(prev).length > 0 ? prev : buildDraft();
                      return {
                        ...base,
                        [item.id]: [
                          ...(base[item.id] ?? []),
                          { key: Date.now(), name: "", productionInstructions: "" },
                        ],
                      };
                    })
                  }
                >
                  <Plus className="size-3.5" /> Add piece
                </Button>
              </div>
              <div className="space-y-2">
                {(effectiveDraft[item.id] ?? []).map((piece, idx) => (
                  <div key={piece.key} className="flex gap-2 items-start">
                    <div className="flex-1 space-y-1.5">
                      <Input
                        placeholder={`Piece name (e.g. Jacket${item.quantity > 1 ? ` #${idx + 1}` : ""})`}
                        value={piece.name}
                        onChange={(e) =>
                          setDraft((prev) => {
                            const base = Object.keys(prev).length > 0 ? prev : buildDraft();
                            return {
                              ...base,
                              [item.id]: (base[item.id] ?? []).map((p) =>
                                p.key === piece.key ? { ...p, name: e.target.value } : p
                              ),
                            };
                          })
                        }
                      />
                      <Input
                        className="text-xs"
                        placeholder="Production instructions (optional)"
                        value={piece.productionInstructions}
                        onChange={(e) =>
                          setDraft((prev) => {
                            const base = Object.keys(prev).length > 0 ? prev : buildDraft();
                            return {
                              ...base,
                              [item.id]: (base[item.id] ?? []).map((p) =>
                                p.key === piece.key ? { ...p, productionInstructions: e.target.value } : p
                              ),
                            };
                          })
                        }
                      />
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      aria-label="Remove piece"
                      onClick={() =>
                        setDraft((prev) => {
                          const base = Object.keys(prev).length > 0 ? prev : buildDraft();
                          return {
                            ...base,
                            [item.id]: (base[item.id] ?? []).filter((p) => p.key !== piece.key),
                          };
                        })
                      }
                    >
                      <Trash2 className="size-4 text-destructive" />
                    </Button>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>

        <DialogFooter className="flex-col sm:flex-row gap-2">
          <Label className="text-sm text-muted-foreground font-normal mr-auto">
            {totalPieces} production task{totalPieces > 1 ? "s" : ""} will be created.
          </Label>
          <Button variant="outline" onClick={() => setOpen(false)}>
            Cancel
          </Button>
          <Button onClick={submit} disabled={createBreakdown.isPending || totalPieces === 0}>
            {createBreakdown.isPending && <Loader2 className="size-4 animate-spin" />} Create tasks
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
