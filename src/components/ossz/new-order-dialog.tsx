"use client";

import { useMemo, useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
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
import { Textarea } from "@/components/ui/textarea";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Plus, Trash2, Loader2, UserPlus, Ruler } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { api } from "./api-client";
import { useNav } from "./use-nav";
import type { Product } from "./types";

type DraftItem = {
  key: number;
  productId: string;
  quantity: number;
  chest: string;
  waist: string;
  sleeve: string;
  instructions: string;
};

const MEASUREMENT_FIELDS: Array<{ key: keyof DraftItem; label: string; placeholder: string }> = [
  { key: "chest", label: "Chest", placeholder: "102 cm" },
  { key: "waist", label: "Waist", placeholder: "88 cm" },
  { key: "sleeve", label: "Sleeve", placeholder: "64 cm" },
];

export function NewOrderDialog() {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const { navigate } = useNav();
  const [open, setOpen] = useState(false);

  const [customerMode, setCustomerMode] = useState<"existing" | "new">("existing");
  const [customerId, setCustomerId] = useState<string>("");
  const [newCustomer, setNewCustomer] = useState({ name: "", email: "", phone: "" });
  const [expectedDeliveryDate, setExpectedDeliveryDate] = useState("");
  const [notes, setNotes] = useState("");
  const [paymentStatus, setPaymentStatus] = useState("UNPAID");
  const [items, setItems] = useState<DraftItem[]>([]);

  const productsQuery = useQuery({
    queryKey: ["products", "active"],
    queryFn: () => api.get<{ products: Product[] }>("/api/products"),
    enabled: open,
  });

  const customersQuery = useQuery({
    queryKey: ["customers"],
    queryFn: () => api.get<{ customers: Array<{ id: string; name: string; phone: string | null }> }>("/api/customers"),
    enabled: open,
  });

  const products = productsQuery.data?.products ?? [];
  const customers = customersQuery.data?.customers ?? [];

  const createOrder = useMutation({
    mutationFn: (payload: unknown) => api.post<{ order: { id: string; reference: string } }>("/api/orders", payload),
    onSuccess: (res) => {
      toast({ title: "Order created", description: `${res.order.reference} has been recorded.` });
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      setOpen(false);
      resetForm();
      navigate({ view: "order", order: res.order.id });
    },
    onError: (err: Error) => {
      toast({ title: "Could not create order", description: err.message, variant: "destructive" });
    },
  });

  function resetForm() {
    setCustomerMode("existing");
    setCustomerId("");
    setNewCustomer({ name: "", email: "", phone: "" });
    setExpectedDeliveryDate("");
    setNotes("");
    setPaymentStatus("UNPAID");
    setItems([]);
  }

  function addItem() {
    setItems((prev) => [
      ...prev,
      {
        key: Date.now() + Math.random(),
        productId: "",
        quantity: 1,
        chest: "",
        waist: "",
        sleeve: "",
        instructions: "",
      },
    ]);
  }

  const totalAmount = useMemo(
    () =>
      items.reduce((sum, item) => {
        const product = products.find((p) => p.id === item.productId);
        return sum + (product ? product.basePrice * item.quantity : 0);
      }, 0),
    [items, products]
  );

  function submit() {
    if (customerMode === "existing" && !customerId) {
      toast({ title: "Select a customer", variant: "destructive" });
      return;
    }
    if (customerMode === "new" && newCustomer.name.trim().length < 2) {
      toast({ title: "Enter the customer name", variant: "destructive" });
      return;
    }
    if (items.length === 0 || items.some((i) => !i.productId)) {
      toast({ title: "Add at least one product to the order", variant: "destructive" });
      return;
    }
    createOrder.mutate({
      ...(customerMode === "existing" ? { customerId } : { customer: newCustomer }),
      items: items.map((i) => {
        const measurements: Record<string, string> = {};
        for (const f of MEASUREMENT_FIELDS) {
          const v = (i[f.key] as string).trim();
          if (v) measurements[f.label] = v;
        }
        return {
          productId: i.productId,
          quantity: i.quantity,
          measurements: Object.keys(measurements).length > 0 ? measurements : undefined,
          instructions: i.instructions.trim() || undefined,
        };
      }),
      expectedDeliveryDate: expectedDeliveryDate
        ? new Date(expectedDeliveryDate + "T17:00:00").toISOString()
        : undefined,
      notes: notes.trim() || undefined,
      paymentStatus,
    });
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="size-4" /> New Order
        </Button>
      </DialogTrigger>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="font-display">New Customer Order</DialogTitle>
          <DialogDescription>
            Record an order taken in the atelier. Production tasks are created separately from the
            order page.
          </DialogDescription>
        </DialogHeader>

        {/* Customer */}
        <div className="space-y-2">
          <Label>Customer</Label>
          <div className="flex gap-2 mb-2">
            <Button
              type="button"
              size="sm"
              variant={customerMode === "existing" ? "default" : "outline"}
              onClick={() => setCustomerMode("existing")}
            >
              Existing
            </Button>
            <Button
              type="button"
              size="sm"
              variant={customerMode === "new" ? "default" : "outline"}
              onClick={() => setCustomerMode("new")}
            >
              <UserPlus className="size-3.5" /> New customer
            </Button>
          </div>
          {customerMode === "existing" ? (
            customersQuery.isLoading ? (
              <Skeleton className="h-9" />
            ) : (
              <Select value={customerId} onValueChange={setCustomerId}>
                <SelectTrigger>
                  <SelectValue placeholder="Select customer…" />
                </SelectTrigger>
                <SelectContent>
                  {customers.map((c) => (
                    <SelectItem key={c.id} value={c.id}>
                      {c.name}
                      {c.phone ? ` · ${c.phone}` : ""}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <Input
                placeholder="Full name *"
                value={newCustomer.name}
                onChange={(e) => setNewCustomer((c) => ({ ...c, name: e.target.value }))}
              />
              <Input
                placeholder="Email"
                value={newCustomer.email}
                onChange={(e) => setNewCustomer((c) => ({ ...c, email: e.target.value }))}
              />
              <Input
                placeholder="Phone"
                value={newCustomer.phone}
                onChange={(e) => setNewCustomer((c) => ({ ...c, phone: e.target.value }))}
              />
            </div>
          )}
        </div>

        {/* Items */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <Label>Items</Label>
            <Button type="button" size="sm" variant="outline" onClick={addItem}>
              <Plus className="size-3.5" /> Add item
            </Button>
          </div>
          {items.length === 0 && (
            <p className="text-sm text-muted-foreground border border-dashed rounded-lg p-4 text-center">
              No items yet — add the garments the customer is ordering.
            </p>
          )}
          <div className="space-y-3">
            {items.map((item, idx) => {
              const product = products.find((p) => p.id === item.productId);
              return (
                <div key={item.key} className="border rounded-lg p-3 space-y-2">
                  <div className="flex items-center gap-2">
                    <Select
                      value={item.productId}
                      onValueChange={(v) =>
                        setItems((prev) => prev.map((i) => (i.key === item.key ? { ...i, productId: v } : i)))
                      }
                    >
                      <SelectTrigger className="flex-1">
                        <SelectValue placeholder="Select product…" />
                      </SelectTrigger>
                      <SelectContent>
                        {products.map((p) => (
                          <SelectItem key={p.id} value={p.id}>
                            {p.name} — {p.basePrice.toLocaleString()} FCFA
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <Input
                      type="number"
                      min={1}
                      max={99}
                      className="w-16 tabular-nums"
                      value={item.quantity}
                      onChange={(e) =>
                        setItems((prev) =>
                          prev.map((i) =>
                            i.key === item.key
                              ? { ...i, quantity: Math.max(1, Math.min(99, Number(e.target.value) || 1)) }
                              : i
                          )
                        )
                      }
                      aria-label="Quantity"
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      onClick={() => setItems((prev) => prev.filter((i) => i.key !== item.key))}
                      aria-label="Remove item"
                    >
                      <Trash2 className="size-4 text-destructive" />
                    </Button>
                  </div>
                  {product && (
                    <p className="text-xs text-muted-foreground">
                      {product.components.length > 0
                        ? `Garment pieces: ${product.components.map((c) => c.name).join(", ")}`
                        : "Single-piece garment"}
                      {" · "}
                      Line total: {(product.basePrice * item.quantity).toLocaleString()} FCFA
                    </p>
                  )}
                  <div className="grid grid-cols-3 gap-2">
                    {MEASUREMENT_FIELDS.map((f) => (
                      <div key={f.key} className="relative">
                        <Ruler className="absolute left-2 top-2.5 size-3.5 text-muted-foreground" />
                        <Input
                          className="pl-7 text-xs"
                          placeholder={`${f.label} (${f.placeholder})`}
                          value={item[f.key] as string}
                          onChange={(e) =>
                            setItems((prev) =>
                              prev.map((i) => (i.key === item.key ? { ...i, [f.key]: e.target.value } : i))
                            )
                          }
                        />
                      </div>
                    ))}
                  </div>
                  <Input
                    className="text-xs"
                    placeholder="Tailoring instructions (fabric, style, fit…)"
                    value={item.instructions}
                    onChange={(e) =>
                      setItems((prev) => prev.map((i) => (i.key === item.key ? { ...i, instructions: e.target.value } : i)))
                    }
                  />
                  <span className="sr-only">Item {idx + 1}</span>
                </div>
              );
            })}
          </div>
        </div>

        {/* Order details */}
        <div className="grid sm:grid-cols-2 gap-3">
          <div className="space-y-2">
            <Label htmlFor="delivery-date">Expected delivery</Label>
            <Input
              id="delivery-date"
              type="date"
              value={expectedDeliveryDate}
              onChange={(e) => setExpectedDeliveryDate(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label>Payment status</Label>
            <Select value={paymentStatus} onValueChange={setPaymentStatus}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="UNPAID">Unpaid</SelectItem>
                <SelectItem value="DEPOSIT_PAID">Deposit paid</SelectItem>
                <SelectItem value="PAID">Paid</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <div className="space-y-2">
          <Label htmlFor="order-notes">Order notes</Label>
          <Textarea
            id="order-notes"
            rows={2}
            placeholder="Occasion, fabric preferences, delivery arrangements…"
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
          />
        </div>

        <DialogFooter className="flex-col sm:flex-row gap-2">
          <p className="text-sm font-semibold mr-auto">
            Order total: {totalAmount.toLocaleString()} FCFA
          </p>
          <Button variant="outline" onClick={() => setOpen(false)}>
            Cancel
          </Button>
          <Button onClick={submit} disabled={createOrder.isPending}>
            {createOrder.isPending && <Loader2 className="size-4 animate-spin" />} Create order
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
