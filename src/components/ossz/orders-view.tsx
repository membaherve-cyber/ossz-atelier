"use client";

import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Progress } from "@/components/ui/progress";
import { Plus, Search, PackageSearch, ChevronLeft, ChevronRight } from "lucide-react";
import { api, qs } from "./api-client";
import { can, useAppStore } from "./store";
import { useNav } from "./use-nav";
import { formatDate, formatFCFA } from "@/lib/format";
import { OrderStatusBadge, PaymentStatusBadge } from "./status-badge";
import { EmptyState } from "./empty-state";
import { NewOrderDialog } from "./new-order-dialog";
import type { OrderListItem, OrderStatus } from "./types";
import { ORDER_STATUS_LABELS } from "./types";

export function OrdersView() {
  const user = useAppStore((s) => s.user)!;
  const { navigate } = useNav();
  const [q, setQ] = useState("");
  const [status, setStatus] = useState<string>("all");
  const [page, setPage] = useState(1);

  const ordersQuery = useQuery({
    queryKey: ["orders", q, status, page],
    queryFn: () =>
      api.get<{ orders: OrderListItem[]; total: number; pageCount: number }>(
        "/api/orders" + qs({ q: q || undefined, status: status === "all" ? undefined : status, page, pageSize: 12 })
      ),
  });

  const orders = ordersQuery.data?.orders ?? [];
  const total = ordersQuery.data?.total ?? 0;
  const pageCount = ordersQuery.data?.pageCount ?? 1;

  return (
    <div className="space-y-4">
      {/* Toolbar */}
      <div className="flex flex-col sm:flex-row gap-2 sm:items-center">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
          <Input
            placeholder="Search reference or customer…"
            className="pl-8"
            value={q}
            onChange={(e) => {
              setQ(e.target.value);
              setPage(1);
            }}
          />
        </div>
        <Select
          value={status}
          onValueChange={(v) => {
            setStatus(v);
            setPage(1);
          }}
        >
          <SelectTrigger className="w-full sm:w-44">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            {(Object.keys(ORDER_STATUS_LABELS) as OrderStatus[]).map((s) => (
              <SelectItem key={s} value={s}>
                {ORDER_STATUS_LABELS[s]}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <div className="flex-1" />
        {can(user, "orders.manage") && <NewOrderDialog />}
      </div>

      {/* Table (desktop) */}
      <Card className="hidden md:block">
        <CardContent className="p-0">
          {ordersQuery.isLoading ? (
            <div className="p-6 space-y-3">
              {[1, 2, 3, 4, 5].map((i) => (
                <Skeleton key={i} className="h-10" />
              ))}
            </div>
          ) : orders.length === 0 ? (
            <EmptyState
              icon={<PackageSearch />}
              title="No orders found"
              description="Try adjusting the filters, or create a new customer order."
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Reference</TableHead>
                  <TableHead>Customer</TableHead>
                  <TableHead>Order Date</TableHead>
                  <TableHead>Delivery</TableHead>
                  <TableHead>Items</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead>Payment</TableHead>
                  <TableHead>Production</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {orders.map((o) => (
                  <TableRow
                    key={o.id}
                    className="cursor-pointer"
                    onClick={() => navigate({ view: "order", order: o.id })}
                  >
                    <TableCell className="font-mono font-semibold text-primary">{o.reference}</TableCell>
                    <TableCell className="font-medium">{o.customer.name}</TableCell>
                    <TableCell className="text-muted-foreground">{formatDate(o.createdAt)}</TableCell>
                    <TableCell className="text-muted-foreground">
                      {formatDate(o.expectedDeliveryDate)}
                    </TableCell>
                    <TableCell className="tabular-nums">{o.itemCount}</TableCell>
                    <TableCell className="tabular-nums">{formatFCFA(o.totalAmount)}</TableCell>
                    <TableCell>
                      <PaymentStatusBadge status={o.paymentStatus} />
                    </TableCell>
                    <TableCell>
                      {o.production.totalPieces > 0 ? (
                        <div className="flex items-center gap-2 min-w-32">
                          <Progress value={o.production.overallProgress} className="h-2 w-20" />
                          <span className="text-xs font-semibold tabular-nums">
                            {o.production.overallProgress}%
                          </span>
                          <span className="text-[10px] text-muted-foreground">
                            ({o.production.approved}/{o.production.totalPieces - o.production.cancelled} ok)
                          </span>
                        </div>
                      ) : (
                        <span className="text-xs text-muted-foreground">—</span>
                      )}
                    </TableCell>
                    <TableCell>
                      <OrderStatusBadge status={o.status} />
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Cards (mobile) */}
      <div className="md:hidden space-y-3">
        {ordersQuery.isLoading ? (
          [1, 2, 3].map((i) => <Skeleton key={i} className="h-24" />)
        ) : orders.length === 0 ? (
          <Card>
            <CardContent>
              <EmptyState
                icon={<PackageSearch />}
                title="No orders found"
                description="Try adjusting the filters."
              />
            </CardContent>
          </Card>
        ) : (
          orders.map((o) => (
            <Card key={o.id} className="cursor-pointer" onClick={() => navigate({ view: "order", order: o.id })}>
              <CardContent className="p-4 space-y-2">
                <div className="flex items-center justify-between gap-2">
                  <p className="font-mono font-semibold text-primary">{o.reference}</p>
                  <OrderStatusBadge status={o.status} />
                </div>
                <p className="font-medium">{o.customer.name}</p>
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span>{formatDate(o.createdAt)}</span>
                  <span>{formatFCFA(o.totalAmount)}</span>
                </div>
                {o.production.totalPieces > 0 && (
                  <div className="flex items-center gap-2">
                    <Progress value={o.production.overallProgress} className="h-2 flex-1" />
                    <span className="text-xs font-semibold tabular-nums">
                      {o.production.overallProgress}%
                    </span>
                  </div>
                )}
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* Pagination */}
      {total > 12 && (
        <div className="flex items-center justify-between">
          <p className="text-xs text-muted-foreground">
            {total} order{total > 1 ? "s" : ""} · page {page} of {pageCount}
          </p>
          <div className="flex gap-1">
            <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
              <ChevronLeft className="size-4" /> Prev
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={page >= pageCount}
              onClick={() => setPage((p) => p + 1)}
            >
              Next <ChevronRight className="size-4" />
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
