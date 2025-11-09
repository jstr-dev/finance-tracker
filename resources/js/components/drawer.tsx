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
          "fixed inset-0 bg-gray-900/30 transition-opacity duration-300 ease-in-out",
          isOpen ? "opacity-20 pointer-events-auto" : "opacity-0 pointer-events-none"
        )}
        style={{ zIndex: 99 }}
        onClick={() => setIsOpen(false)}
      />

      {/* Drawer */}
      <div
        className={cn(
          "fixed top-[5px] right-[5px] h-[calc(100vh-10px)] w-full sm:w-[450px] max-w-[650px] bg-background shadow-lg transform transition-transform duration-300 ease-in-out rounded-xl border-2 border-background z-100 flex flex-col",
          isOpen ? "translate-x-0" : "translate-x-full"
        )}
      >
        <div className="flex-1 overflow-y-auto p-6">{children}</div>
      </div>
    </>
  );
}
