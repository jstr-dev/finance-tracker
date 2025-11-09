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
      <div
        className={cn(
          "fixed inset-0 bg-black bg-opacity-40 transition-opacity",
          isOpen ? "opacity-100 pointer-events-auto" : "opacity-0 pointer-events-none"
        )}
        onClick={() => setIsOpen(false)}
      ></div>

      <div
        className={cn(
          "fixed top-0 right-0 h-full bg-white shadow-lg transition-all duration-300 ease-in-out overflow-y-auto",
          isOpen
            ? "w-1/3 max-sm:w-full max-w-[650px]"
            : "w-0"
        )}
      >
        <div className="p-6">{children}</div>
      </div>
    </>
  );
}
