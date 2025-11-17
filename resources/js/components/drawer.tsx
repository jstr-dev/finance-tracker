import { cn } from "@/lib/utils";
import { ReactNode, useEffect } from "react";

export interface DrawerProps {
  isOpen: boolean;
  setIsOpen: (open: boolean) => void;
  children?: ReactNode;
}

export default function Drawer({ isOpen, setIsOpen, children }: DrawerProps) {
  useEffect(() => {
    document.body.style.overflow = isOpen ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [isOpen]);

  return (
    <>
      {/* Overlay */}
      <div
        className={cn(
            "overlay",
            isOpen && "active"
        )}
        style={{ zIndex: 99 }}
        onClick={() => setIsOpen(false)}
      />

      {/* Drawer */}
      <div
        className={cn(
            "fixed top-0 right-0 h-full w-full sm:w-[400px] max-w-[400px]",
            "bg-drawer shadow-lg transform transition-transform duration-300 ease-in-out border-l-1 border-muted-foreground z-99 flex flex-col",
          isOpen ? "translate-x-0" : "translate-x-full"
        )}
      >
        <div className="flex-1 overflow-y-auto p-6">{children}</div>
      </div>
    </>
  );
}

export function DrawerCloseButton({ setIsOpen }: { setIsOpen: (open: boolean) => void }) {
    return (
        <div
            className={cn("absolute top-6 right-4 z-100",
                "flex h-8 w-8 items-center justify-center rounded-full",
                "hover:bg-drawer-foreground/5 cursor-pointer text-foreground/60")}
            onClick={() => setIsOpen(false)}
        >
            <span aria-hidden="true" className="text-3xl">&times;</span>
        </div>
    );
}

export function DrawerFooter({ children, className }: { children: ReactNode, className?: string }) {
    return <div className={cn("flex flex-col", className)}>
        {children}
    </div>
}