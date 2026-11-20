"use client";

// ErrorBoundary — catches render crashes anywhere in the Atelier tree and
// shows a recoverable fallback instead of a blank white screen. This is the
// last line of defence against a 500/blank-page experience for the user.

import { Component, type ErrorInfo, type ReactNode } from "react";
import { AlertTriangle, RefreshCw } from "lucide-react";

interface Props {
  children: ReactNode;
  /** Optional label shown in the fallback to indicate which area failed. */
  label?: string;
}

interface State {
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    // Log to console for ops visibility without leaking to the user.
    console.error("[atelier] render crash:", error, info.componentStack);
  }

  private reset = (): void => {
    this.setState({ error: null });
  };

  render(): ReactNode {
    if (this.state.error) {
      return (
        <div className="flex min-h-[60vh] flex-col items-center justify-center gap-4 p-8 text-center">
          <div className="flex size-14 items-center justify-center rounded-2xl bg-destructive/10">
            <AlertTriangle className="size-7 text-destructive" />
          </div>
          <div className="space-y-1">
            <p className="font-display text-lg font-semibold">
              Something went wrong
            </p>
            <p className="max-w-md text-sm text-muted-foreground">
              {this.props.label
                ? `The ${this.props.label} could not be displayed.`
                : "This screen could not be displayed."}{" "}
              Try reloading — your data is safe.
            </p>
          </div>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={this.reset}
              className="inline-flex items-center gap-2 rounded-lg border border-border bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
            >
              <RefreshCw className="size-4" />
              Try again
            </button>
            <button
              type="button"
              onClick={() => window.location.reload()}
              className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
            >
              Reload page
            </button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}
