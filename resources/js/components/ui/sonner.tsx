import { useAppearance } from "@/hooks/use-appearance"
import {
  Toaster as Sonner,
  type ToasterProps as SonnerToasterProps,
} from "sonner"

type ToasterProps = SonnerToasterProps

function Toaster(props: ToasterProps) {
  const { resolvedAppearance } = useAppearance()

  return <Sonner theme={resolvedAppearance} richColors {...props} />
}

export { Toaster }
