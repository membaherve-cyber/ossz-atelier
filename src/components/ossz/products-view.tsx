"use client";

import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Plus, Package, Trash2, Loader2, Scissors, Pencil } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { api } from "./api-client";
import { can, useAppStore } from "./store";
import { formatFCFA } from "@/lib/format";
import { EmptyState } from "./empty-state";
import type { Product } from "./types";

type ComponentDraft = { key: number; name: string; defaultInstructions: string };

export function ProductsView() {
  const user = useAppStore((s) => s.user)!;
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const canManage = can(user, "products.manage");

  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Product | null>(null);
  const [form, setForm] = useState({
    name: "",
    category: "",
    basePrice: "",
    description: "",
    components: [] as ComponentDraft[],
  });

  const productsQuery = useQuery({
    queryKey: ["products", "all"],
    queryFn: () => api.get<{ products: Product[] }>("/api/products?all=1"),
  });

  const products = productsQuery.data?.products ?? [];

  const saveMutation = useMutation({
    mutationFn: (payload: unknown) =>
      editing ? api.patch(`/api/products/${editing.id}`, payload) : api.post("/api/products", payload),
    onSuccess: () => {
      toast({ title: editing ? "Product updated" : "Product created" });
      queryClient.invalidateQueries({ queryKey: ["products"] });
      setDialogOpen(false);
      setEditing(null);
    },
    onError: (err: Error) => {
      toast({ title: "Could not save product", description: err.message, variant: "destructive" });
    },
  });

  const toggleActive = useMutation({
    mutationFn: (p: Product) => api.patch(`/api/products/${p.id}`, { isActive: !p.isActive }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["products"] });
    },
    onError: (err: Error) => {
      toast({ title: "Could not update product", description: err.message, variant: "destructive" });
    },
  });

  function openCreate() {
    setEditing(null);
    setForm({ name: "", category: "", basePrice: "", description: "", components: [] });
    setDialogOpen(true);
  }

  function openEdit(p: Product) {
    setEditing(p);
    setForm({
      name: p.name,
      category: p.category,
      basePrice: String(p.basePrice),
      description: p.description ?? "",
      components: p.components.map((c, i) => ({
        key: i,
        name: c.name,
        defaultInstructions: c.defaultInstructions ?? "",
      })),
    });
    setDialogOpen(true);
  }

  function submit() {
    if (form.name.trim().length < 2 || form.category.trim().length < 2 || !form.basePrice) {
      toast({ title: "Fill in name, category and price", variant: "destructive" });
      return;
    }
    saveMutation.mutate({
      name: form.name.trim(),
      category: form.category.trim(),
      basePrice: Number(form.basePrice) || 0,
      description: form.description.trim() || null,
      components: form.components
        .filter((c) => c.name.trim())
        .map((c) => ({
          name: c.name.trim(),
          defaultInstructions: c.defaultInstructions.trim() || undefined,
        })),
    });
  }

  return (
    <div className="space-y-4">
      {canManage && (
        <div className="flex justify-end">
          <Button onClick={openCreate}>
            <Plus className="size-4" /> New Product
          </Button>
        </div>
      )}

      {productsQuery.isLoading ? (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <Skeleton key={i} className="h-44" />
          ))}
        </div>
      ) : products.length === 0 ? (
        <Card>
          <CardContent>
            <EmptyState icon={<Package />} title="No products" description="Add the garments your atelier produces." />
          </CardContent>
        </Card>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
          {products.map((p) => (
            <Card key={p.id} className={!p.isActive ? "opacity-60" : ""}>
              <CardContent className="p-4 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="font-semibold">{p.name}</p>
                    <p className="text-xs text-muted-foreground">{p.category}</p>
                  </div>
                  <p className="font-bold tabular-nums text-sm">{formatFCFA(p.basePrice)}</p>
                </div>
                {p.description && <p className="text-xs text-muted-foreground line-clamp-2">{p.description}</p>}
                <div>
                  <p className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">
                    Garment pieces
                  </p>
                  <div className="flex flex-wrap gap-1">
                    {p.components.length === 0 ? (
                      <Badge variant="outline" className="font-normal text-xs">Single piece</Badge>
                    ) : (
                      p.components.map((c) => (
                        <Badge key={c.id} variant="secondary" className="font-normal">
                          <Scissors className="size-3 mr-1" />
                          {c.name}
                        </Badge>
                      ))
                    )}
                  </div>
                </div>
                {canManage && (
                  <div className="flex items-center justify-between pt-1 border-t">
                    <div className="flex items-center gap-2">
                      <Switch
                        checked={p.isActive}
                        onCheckedChange={() => toggleActive.mutate(p)}
                        aria-label="Toggle active"
                      />
                      <span className="text-xs text-muted-foreground">{p.isActive ? "Active" : "Archived"}</span>
                    </div>
                    <Button variant="ghost" size="sm" className="h-7" onClick={() => openEdit(p)}>
                      <Pencil className="size-3.5" /> Edit
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Create / edit dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="font-display">{editing ? "Edit Product" : "New Product"}</DialogTitle>
            <DialogDescription>
              Define the garment and its production components. Each component becomes a separate
              production task when the product is ordered.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-3">
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label>Product name</Label>
                <Input
                  placeholder="e.g. Three-Piece Suit"
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                />
              </div>
              <div className="space-y-1.5">
                <Label>Category</Label>
                <Input
                  placeholder="e.g. Suits, Traditional"
                  value={form.category}
                  onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))}
                />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label>Base price (FCFA)</Label>
              <Input
                type="number"
                min={0}
                value={form.basePrice}
                onChange={(e) => setForm((f) => ({ ...f, basePrice: e.target.value }))}
              />
            </div>
            <div className="space-y-1.5">
              <Label>Description</Label>
              <Textarea
                rows={2}
                value={form.description}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
              />
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label>Garment components</Label>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() =>
                    setForm((f) => ({
                      ...f,
                      components: [...f.components, { key: Date.now(), name: "", defaultInstructions: "" }],
                    }))
                  }
                >
                  <Plus className="size-3.5" /> Add component
                </Button>
              </div>
              <p className="text-xs text-muted-foreground">
                Leave empty for single-piece garments — the whole product becomes one production task.
              </p>
              {form.components.map((c, idx) => (
                <div key={c.key} className="flex gap-2 items-start">
                  <div className="flex-1 space-y-1">
                    <Input
                      placeholder={`Component ${idx + 1} name (e.g. Jacket)`}
                      value={c.name}
                      onChange={(e) =>
                        setForm((f) => ({
                          ...f,
                          components: f.components.map((x) =>
                            x.key === c.key ? { ...x, name: e.target.value } : x
                          ),
                        }))
                      }
                    />
                    <Input
                      className="text-xs"
                      placeholder="Default production instructions (optional)"
                      value={c.defaultInstructions}
                      onChange={(e) =>
                        setForm((f) => ({
                          ...f,
                          components: f.components.map((x) =>
                            x.key === c.key ? { ...x, defaultInstructions: e.target.value } : x
                          ),
                        }))
                      }
                    />
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Remove component"
                    onClick={() =>
                      setForm((f) => ({ ...f, components: f.components.filter((x) => x.key !== c.key) }))
                    }
                  >
                    <Trash2 className="size-4 text-destructive" />
                  </Button>
                </div>
              ))}
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={submit} disabled={saveMutation.isPending}>
              {saveMutation.isPending && <Loader2 className="size-4 animate-spin" />}
              {editing ? "Save changes" : "Create product"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
