import { useEffect, useState } from "react"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

interface AbsensiEditDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  data: any
  onSave: (id: number, data: any) => void
}

export default function AbsensiEditDialog({
  open,
  onOpenChange,
  data,
  onSave,
}: AbsensiEditDialogProps) {
  const [formData, setFormData] = useState<any>({})

  useEffect(() => {
    if (data) {
      setFormData({ ...data })
    }
  }, [data])

  const handleSave = () => {
    if (data?.id) {
      onSave(data.id, formData)
    }
    onOpenChange(false)
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Edit Absensi</DialogTitle>
          <DialogDescription>
            Ubah data absensi secara manual.
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-4 py-4">
          <div className="grid grid-cols-4 items-center gap-4">
            <Label htmlFor="jam_masuk" className="text-right">
              Jam Masuk
            </Label>
            <Input
              id="jam_masuk"
              type="time"
              value={formData.jam_masuk || ""}
              onChange={(e) =>
                setFormData({ ...formData, jam_masuk: e.target.value })
              }
              className="col-span-3"
            />
          </div>
          <div className="grid grid-cols-4 items-center gap-4">
            <Label htmlFor="jam_keluar" className="text-right">
              Jam Keluar
            </Label>
            <Input
              id="jam_keluar"
              type="time"
              value={formData.jam_keluar || ""}
              onChange={(e) =>
                setFormData({ ...formData, jam_keluar: e.target.value })
              }
              className="col-span-3"
            />
          </div>
          <div className="grid grid-cols-4 items-center gap-4">
            <Label htmlFor="status" className="text-right">
              Status
            </Label>
            <Select
              value={formData.status}
              onValueChange={(value) =>
                setFormData({ ...formData, status: value })
              }
            >
              <SelectTrigger className="col-span-3">
                <SelectValue placeholder="Pilih Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="hadir">Hadir</SelectItem>
                <SelectItem value="izin">Izin</SelectItem>
                <SelectItem value="sakit">Sakit</SelectItem>
                <SelectItem value="alpa">Alpa</SelectItem>
                <SelectItem value="libur">Libur</SelectItem>
              </SelectContent>
            </Select>
          </div>
           <div className="grid grid-cols-4 items-center gap-4">
            <Label htmlFor="keterangan" className="text-right">
              Keterangan
            </Label>
            <Input
              id="keterangan"
              value={formData.keterangan || ""}
              onChange={(e) =>
                setFormData({ ...formData, keterangan: e.target.value })
              }
              className="col-span-3"
            />
          </div>
        </div>
        <DialogFooter>
          <Button type="submit" onClick={handleSave}>
            Simpan
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
