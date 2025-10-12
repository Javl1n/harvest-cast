import { useAtomValue, useSetAtom } from "jotai";
import { useEffect, useRef } from "react";
import { getPanelGroupElement, PanelGroupOnLayout } from "react-resizable-panels";

export function useSetPanelSize(size: number) {
     const panelRef = useRef<any>(null);

     useEffect(() => {
          const panelGroup = getPanelGroupElement('panel-group');
          panelRef.current = panelGroup;

          // panelRef.current.setLayout([size, 100-size]);

          console.log(panelRef.current)

     }, [size])

     // setPanelSizes([size, 100-size])
          
}