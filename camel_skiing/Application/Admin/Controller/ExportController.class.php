<?php

namespace Admin\Controller;

use Admin\Controller\BaseController;

class ExportController extends BaseController{
    
    /**
     * 订单信息导出
     */
   public function order_export(){  
        $where = '1=1';       
        $OrdersData =  M('order')
                    ->alias('tor')
                    ->field('tor.*,a.theme_name,t.attr_value')
                    ->join([
                        ['__ACTIVITY__ AS a ON a.id = tor.activity_id', 'INNER'],
                        ['__ACTIVITY_ATTR__ AS t ON t.attr_id = tor.activity_attr_id', 'INNER']
                    ])
                    ->where($where)
                    ->select();//查询数据得到$OrdersData二维数组  
  
        vendor("PHPExcel.PHPExcel");  
  
        // Create new PHPExcel object  
        $objPHPExcel = new \PHPExcel();  
        // Set properties  
        $objPHPExcel->getProperties()->setCreator("ctos")  
            ->setLastModifiedBy("ctos")  
            ->setTitle("Office 2007 XLSX Test Document")  
            ->setSubject("Office 2007 XLSX Test Document")  
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")  
            ->setKeywords("office 2007 openxml php")  
            ->setCategory("Test result file");  
  
        //set width  
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);  
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(25); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(18); 
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(25); 
  
        //设置行高度  
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);  
  
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);  
  
        //set font size bold  
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:J2')->getFont()->setBold(true);  
  
        $objPHPExcel->getActiveSheet()->getStyle('A2:J2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('A2:J2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);  
  
        //设置水平居中  
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);  
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
        $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
  
        //合并cell  
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');  
  
        // set table header content  
        $objPHPExcel->setActiveSheetIndex(0)  
            ->setCellValue('A1', '报名订单记录汇总  时间:'.date('Y-m-d H:i:s'))  
            ->setCellValue('A2', '报名号')  
            ->setCellValue('B2', '比赛主题')  
            ->setCellValue('C2', '比赛选项')  
            ->setCellValue('D2', '报名人数')  
            ->setCellValue('E2', '总费用(单位：元)')  
            ->setCellValue('F2', '下单时间时间');  
  
        // Miscellaneous glyphs, UTF-8  
        for($i=0;$i<count($OrdersData);$i++){  
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3), ' '.$OrdersData[$i]['order_sn']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+3), $OrdersData[$i]['theme_name']);  
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3), $OrdersData[$i]['attr_value']); 
            $objPHPExcel->getActiveSheet(0)->setCellValue('D'.($i+3), $OrdersData[$i]['user_num']); 
            $objPHPExcel->getActiveSheet(0)->setCellValue('E'.($i+3), $OrdersData[$i]['price']); 
            $objPHPExcel->getActiveSheet(0)->setCellValue('F'.($i+3), date('Y-m-d H:i:s',$OrdersData[$i]['add_time']));  
        }  
  
  
        //  sheet命名  
        $objPHPExcel->getActiveSheet()->setTitle('报名订单');  
  
  
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet  
        $objPHPExcel->setActiveSheetIndex(0);  
  
  
        // excel头参数  
        header('Content-Type: application/vnd.ms-excel');  
        header('Content-Disposition: attachment;filename="活动报名订单表('.date('Ymd-His').').xls"');  //日期为文件名后缀  
        header('Cache-Control: max-age=0');  
  
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式  
        $objWriter->save('php://output');  
  
    }   
    
    
}  
