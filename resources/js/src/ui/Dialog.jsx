import * as React from 'react';
import { XIcon } from 'lucide-react';

function Dialog({ open, onOpenChange, children }) {
  if (!open) return null;
  return (
    <div data-slot="dialog" className="fixed inset-0 z-50">
      {children}
    </div>
  );
}

function DialogTrigger({ asChild, children, ...props }) {
  if (asChild) {
    return React.cloneElement(children, props);
  }
  return (
    <button data-slot="dialog-trigger" {...props}>
      {children}
    </button>
  );
}

function DialogPortal({ children }) {
  return <div data-slot="dialog-portal">{children}</div>;
}

function DialogClose({ className = '', ...props }) {
  return (
    <button
      data-slot="dialog-close"
      className={`rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:pointer-events-none ${className}`}
      {...props}
    />
  );
}

function DialogOverlay({ className = '', ...props }) {
  return (
    <div
      data-slot="dialog-overlay"
      className={`fixed inset-0 z-50 bg-black/50 animate-in fade-in-0 ${className}`}
      {...props}
    />
  );
}

function DialogContent({ className = '', children, ...props }) {
  return (
    <DialogPortal>
      <DialogOverlay />
      <div
        data-slot="dialog-content"
        className={`bg-neutral-900 text-neutral-100 fixed top-1/2 left-1/2 z-50 grid w-full max-w-[calc(100%-2rem)] sm:max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border border-neutral-700 p-6 shadow-lg animate-in fade-in-0 zoom-in-95 ${className}`}
        {...props}
      >
        {children}
        <DialogClose className="absolute top-4 right-4">
          <XIcon className="h-4 w-4" />
          <span className="sr-only">Close</span>
        </DialogClose>
      </div>
    </DialogPortal>
  );
}

function DialogHeader({ className = '', ...props }) {
  return (
    <div
      data-slot="dialog-header"
      className={`flex flex-col gap-2 text-center sm:text-left ${className}`}
      {...props}
    />
  );
}

function DialogFooter({ className = '', ...props }) {
  return (
    <div
      data-slot="dialog-footer"
      className={`flex flex-col-reverse gap-2 sm:flex-row sm:justify-end ${className}`}
      {...props}
    />
  );
}

function DialogTitle({ className = '', ...props }) {
  return (
    <h2
      data-slot="dialog-title"
      className={`text-lg leading-none font-semibold text-neutral-100 ${className}`}
      {...props}
    />
  );
}

function DialogDescription({ className = '', ...props }) {
  return (
    <p
      data-slot="dialog-description"
      className={`text-neutral-400 text-sm ${className}`}
      {...props}
    />
  );
}

export {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogOverlay,
  DialogPortal,
  DialogTitle,
  DialogTrigger,
};
